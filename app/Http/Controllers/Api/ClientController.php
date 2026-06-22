<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return ClientResource::collection(
            $team->clients()
                ->with('branch')
                ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(Request $request, Team $team): ClientResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());

        $data = $request->validate($this->rules($team, true));

        $client = $team->clients()->create($data);

        return new ClientResource($client->load('branch'));
    }

    public function show(Request $request, Team $team, Client $client): ClientResource
    {
        $this->authorizeTeam($request, $team);
        abort_unless((int) $client->team_id === (int) $team->id, 404);

        return new ClientResource($client->load([
            'branch',
            'motorcycles.brand',
            'motorcycles.motorcycleModel',
            'workOrders.branch',
            'workOrders.motorcycle.brand',
            'workOrders.motorcycle.motorcycleModel',
            'workOrders.maintenanceType',
        ]));
    }

    public function update(Request $request, Team $team, Client $client): ClientResource
    {
        $this->authorizeTeamRole($request, $team, $this->operationalRoles());
        abort_unless((int) $client->team_id === (int) $team->id, 404);

        $client->update($request->validate($this->rules($team, false)));

        return new ClientResource($client->load('branch'));
    }

    private function rules(Team $team, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'email' => [$required, 'email', 'max:255'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('team_id', $team->id),
            ],
        ];
    }
}
