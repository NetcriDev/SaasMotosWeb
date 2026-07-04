<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\MotorcycleResource;
use App\Models\Motorcycle;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MotorcycleController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return MotorcycleResource::collection(
            $team->motorcycles()
                ->with(['client', 'branch', 'brand', 'motorcycleModel'])
                ->when($request->integer('client_id'), fn ($query, int $clientId) => $query->where('client_id', $clientId))
                ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                    $search = $request->string('search')->toString();

                    $query->where('license_plate', 'like', "%{$search}%");
                })
                ->orderBy('license_plate')
                ->get(),
        );
    }

    public function store(Request $request, Team $team): MotorcycleResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());

        $this->normalizeLicensePlate($request);

        $data = $request->validate($this->rules($team, true));

        $motorcycle = $team->motorcycles()->create($data);

        return new MotorcycleResource($motorcycle->load(['client', 'branch', 'brand', 'motorcycleModel']));
    }

    public function show(Request $request, Team $team, Motorcycle $motorcycle): MotorcycleResource
    {
        $this->authorizeTeam($request, $team);
        abort_unless((int) $motorcycle->team_id === (int) $team->id, 404);

        return new MotorcycleResource($motorcycle->load([
            'client',
            'branch',
            'brand',
            'motorcycleModel',
            'workOrders.branch',
            'workOrders.client',
            'workOrders.maintenanceType',
        ]));
    }

    public function update(Request $request, Team $team, Motorcycle $motorcycle): MotorcycleResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $motorcycle->team_id === (int) $team->id, 404);

        $this->normalizeLicensePlate($request);

        $data = $request->validate($this->rules($team, false, $motorcycle));

        $motorcycle->update($data);

        return new MotorcycleResource($motorcycle->load(['client', 'branch', 'brand', 'motorcycleModel']));
    }

    private function rules(Team $team, bool $creating, ?Motorcycle $motorcycle = null): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'client_id' => [$required, Rule::exists('clients', 'id')->where('team_id', $team->id)],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('team_id', $team->id)],
            'license_plate' => [
                $required,
                'string',
                'max:32',
                Rule::unique('motorcycles', 'license_plate')
                    ->where('team_id', $team->id)
                    ->ignore($motorcycle),
            ],
            'brand_id' => [$required, Rule::exists('brands', 'id')->where('team_id', $team->id)],
            'motorcycle_model_id' => [$required, Rule::exists('motorcycle_models', 'id')->where('team_id', $team->id)],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
        ];
    }

    private function normalizeLicensePlate(Request $request): void
    {
        if ($request->has('license_plate')) {
            $request->merge([
                'license_plate' => strtoupper(trim((string) $request->input('license_plate'))),
            ]);
        }
    }
}
