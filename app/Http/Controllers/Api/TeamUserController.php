<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamUserResource;
use App\Models\Team;
use App\Support\TenancyPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TeamUserController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeamRole($request, $team, $this->supervisorRoles());

        $data = $request->validate([
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('team_id', $team->id)],
        ]);

        return TeamUserResource::collection(
            $this->members($team, $data['role'] ?? null),
        );
    }

    public function mechanics(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return TeamUserResource::collection(
            $this->members($team, 'mecanico'),
        );
    }

    private function members(Team $team, ?string $role = null): Collection
    {
        return TenancyPermissions::withTeam(
            $team,
            fn (): Collection => $team->members()
                ->when($role, function (Builder $query, string $role) use ($team): void {
                    $query->whereHas('roles', function (Builder $query) use ($role, $team): void {
                        $query->where('roles.name', $role)
                            ->where('roles.team_id', $team->id);
                    });
                })
                ->orderBy('name')
                ->get(),
        );
    }
}
