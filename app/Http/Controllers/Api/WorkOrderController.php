<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderResource;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\DefaultWorkshopCatalog;
use App\Support\TenancyPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return WorkOrderResource::collection(
            $team->workOrders()
                ->with(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'mechanic', 'maintenanceType', 'activities'])
                ->when($request->boolean('active'), fn ($query) => $query->whereIn('status', WorkOrderStatus::activeValues()))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($request->integer('branch_id'), fn ($query, int $branchId) => $query->where('branch_id', $branchId))
                ->when($request->integer('client_id'), fn ($query, int $clientId) => $query->where('client_id', $clientId))
                ->when($request->integer('motorcycle_id'), fn ($query, int $motorcycleId) => $query->where('motorcycle_id', $motorcycleId))
                ->when($request->filled('received_from'), fn ($query) => $query->whereDate('received_at', '>=', $request->date('received_from')))
                ->when($request->filled('received_until'), fn ($query) => $query->whereDate('received_at', '<=', $request->date('received_until')))
                ->latest('received_at')
                ->get(),
        );
    }

    public function store(Request $request, Team $team): WorkOrderResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());

        $data = $request->validate($this->rules($team, true));
        $activities = $data['activities'] ?? [];
        unset($data['activities']);

        $data['status'] ??= WorkOrderStatus::Received->value;

        $data['mechanic_id'] ??= $this->defaultMechanicId($request, $team);

        $order = DB::transaction(function () use ($team, $data, $activities): WorkOrder {
            $order = $team->workOrders()->create($data);

            $order->activities()->createMany($activities);
            $order->recalculateEstimatedTotal();

            return $order;
        });

        return new WorkOrderResource($order->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'mechanic', 'maintenanceType', 'activities']));
    }

    public function show(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeam($request, $team);
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'mechanic', 'maintenanceType', 'activities']));
    }

    public function update(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        $data = $request->validate($this->rules($team, false));
        $hasActivities = array_key_exists('activities', $data);
        $activities = $data['activities'] ?? [];
        unset($data['activities']);

        DB::transaction(function () use ($workOrder, $data, $hasActivities, $activities): void {
            $workOrder->update($data);

            if ($hasActivities) {
                $workOrder->activities()->delete();
                $workOrder->activities()->createMany($activities);
            }

            $workOrder->recalculateEstimatedTotal();
        });

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'mechanic', 'maintenanceType', 'activities']));
    }

    public function updateStatus(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
        ]);

        $workOrder->update($data);

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'mechanic', 'maintenanceType', 'activities']));
    }

    private function rules(Team $team, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';
        DefaultWorkshopCatalog::ensureForTeam($team);

        return [
            'branch_id' => [$required, Rule::exists('branches', 'id')->where('team_id', $team->id)],
            'client_id' => [$required, Rule::exists('clients', 'id')->where('team_id', $team->id)],
            'motorcycle_id' => [$required, Rule::exists('motorcycles', 'id')->where('team_id', $team->id)],
            'mechanic_id' => ['nullable', 'integer', function (string $attribute, mixed $value, \Closure $fail) use ($team): void {
                if (blank($value)) {
                    return;
                }

                if (! $this->userIsMechanicOfTeam((int) $value, $team)) {
                    $fail('El empleado asignado debe ser mecanico de este taller.');
                }
            }],
            'maintenance_type_id' => ['nullable', Rule::exists('maintenance_types', 'id')->where('team_id', $team->id)],
            'intake_reason' => ['nullable', 'string'],
            'affected_systems' => ['nullable', 'array'],
            'affected_systems.*' => ['string', Rule::exists('motorcycle_systems', 'code')->where('team_id', $team->id)],
            'activities' => ['sometimes', 'array'],
            'activities.*.system' => ['nullable', 'string', Rule::exists('motorcycle_systems', 'code')->where('team_id', $team->id)],
            'activities.*.description' => ['required_with:activities', 'string', 'max:255'],
            'activities.*.service_cost' => ['nullable', 'numeric', 'min:0'],
            'activities.*.is_billable' => ['nullable', 'boolean'],
            'activities.*.notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(WorkOrderStatus::class)],
            'received_at' => [$required, 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'estimated_total' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function defaultMechanicId(Request $request, Team $team): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User || ! $this->userIsMechanicOfTeam($user->getKey(), $team)) {
            return null;
        }

        return $user->getKey();
    }

    private function userIsMechanicOfTeam(int $userId, Team $team): bool
    {
        $user = User::query()->find($userId);

        return $user instanceof User
            && $user->teams()->whereKey($team->getKey())->exists()
            && TenancyPermissions::withTeam($team, fn (): bool => $user->hasRole('mecanico'));
    }
}
