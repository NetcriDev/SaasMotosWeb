<?php

namespace App\Support;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;

final class TenancyPermissions
{
    public static function setTeam(?Team $team): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($team?->getKey());
    }

    public static function setTeamFromFilament(): void
    {
        $tenant = Filament::getTenant();

        self::setTeam($tenant instanceof Team ? $tenant : null);
    }

    public static function withTeam(Team $team, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        self::setTeam($team);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    public static function userHasPermission(User $user, string $permission, ?Team $team = null): bool
    {
        $team ??= Filament::getTenant();

        if (! $team instanceof Team) {
            return false;
        }

        return self::withTeam($team, fn (): bool => $user->hasPermissionTo($permission));
    }

    public static function assignRole(User $user, string $role, Team $team): void
    {
        self::withTeam($team, function () use ($user, $role): void {
            $user->syncRoles([$role]);
        });
    }

    public static function removeRoles(User $user, Team $team): void
    {
        self::withTeam($team, function () use ($user): void {
            $user->syncRoles([]);
        });
    }

    public static function userCanManageInvitations(User $user, ?Team $team = null): bool
    {
        if ($team instanceof Team) {
            return self::userHasPermission($user, 'ManageTeamInvitations', $team);
        }

        return $user->teams()
            ->get()
            ->contains(fn (Team $candidate): bool => self::userHasPermission($user, 'ManageTeamInvitations', $candidate));
    }
}
