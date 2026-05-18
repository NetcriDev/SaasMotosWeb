<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;

final class ShieldBootstrap
{
    public static function assignSuperAdmin(User $user, Team $team): void
    {
        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        TenancyPermissions::withTeam($team, function () use ($user, $team, $roleName): void {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $team->getKey(),
                ],
            );

            $role->syncPermissions(Permission::query()->pluck('id'));

            $user->syncRoles([$role]);
        });
    }

    public static function ensureInvitableRole(Team $team, string $roleName): void
    {
        TenancyPermissions::withTeam($team, function () use ($team, $roleName): void {
            Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $team->getKey(),
                ],
            );
        });
    }
}
