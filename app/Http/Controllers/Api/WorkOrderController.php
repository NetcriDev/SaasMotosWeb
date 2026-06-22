<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderResource;
use App\Models\Team;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return WorkOrderResource::collection(
            $team->workOrders()
                ->with(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType'])
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
        $data['status'] ??= WorkOrderStatus::Received->value;

        $order = $team->workOrders()->create($data);

        return new WorkOrderResource($order->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType']));
    }

    public function show(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeam($request, $team);
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType']));
    }

    public function update(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        $workOrder->update($request->validate($this->rules($team, false)));

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType']));
    }

    public function updateStatus(Request $request, Team $team, WorkOrder $workOrder): WorkOrderResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $workOrder->team_id === (int) $team->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
        ]);

        $workOrder->update($data);

        return new WorkOrderResource($workOrder->load(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType']));
    }

    private function rules(Team $team, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'branch_id' => [$required, Rule::exists('branches', 'id')->where('team_id', $team->id)],
            'client_id' => [$required, Rule::exists('clients', 'id')->where('team_id', $team->id)],
            'motorcycle_id' => [$required, Rule::exists('motorcycles', 'id')->where('team_id', $team->id)],
            'maintenance_type_id' => ['nullable', Rule::exists('maintenance_types', 'id')->where('team_id', $team->id)],
            'status' => ['sometimes', 'required', Rule::enum(WorkOrderStatus::class)],
            'received_at' => [$required, 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'estimated_total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
