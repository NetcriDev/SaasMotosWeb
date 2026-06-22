<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Team;
use App\Models\User;
use App\Support\TenancyPermissions;
use Illuminate\Http\Request;

trait AuthorizesTeamAccess
{
    protected function authorizeTeam(Request $request, Team $team): void
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->teams()->whereKey($team->getKey())->exists(), 403, 'No tienes acceso a este taller.');
    }

    /**
     * @param  list<string>  $roles
     */
    protected function authorizeTeamRole(Request $request, Team $team, array $roles): void
    {
        $this->authorizeTeam($request, $team);

        /** @var User $user */
        $user = $request->user();

        $hasRole = TenancyPermissions::withTeam(
            $team,
            fn (): bool => $user->hasAnyRole($roles),
        );

        abort_unless($hasRole, 403, 'Tu rol no tiene permiso para realizar esta accion.');
    }

    /**
     * @return list<string>
     */
    protected function operationalRoles(): array
    {
        return ['super_admin', 'admin', 'recepcion', 'mecanico'];
    }

    /**
     * @return list<string>
     */
    protected function supervisorRoles(): array
    {
        return ['super_admin', 'admin'];
    }
}
