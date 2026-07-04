<?php

namespace App\Support;

use App\Enums\TeamRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;

final class ShieldBootstrap
{
    /**
     * Roles operativos de un taller. El super admin del sistema no es un rol de team.
     *
     * @return list<string>
     */
    public static function defaultTeamRoleNames(): array
    {
        return [
            TeamRole::Supervisor->value,
            TeamRole::Recepcion->value,
            TeamRole::Mecanico->value,
        ];
    }

    public static function assignSupervisor(User $user, Team $team): void
    {
        self::ensureDefaultTeamRoles($team);

        TenancyPermissions::assignRole($user, TeamRole::Supervisor->value, $team);
    }

    /**
     * Alias histórico: el creador del taller es supervisor del team, no super admin del sistema.
     */
    public static function assignSuperAdmin(User $user, Team $team): void
    {
        self::assignSupervisor($user, $team);
    }

    public static function ensureDefaultTeamRoles(Team $team): void
    {
        TenancyPermissions::withTeam($team, function () use ($team): void {
            // Supervisor recibe todos los permisos Spatie existentes (generados por shield:generate).
            self::role($team, TeamRole::Supervisor->value)
                ->syncPermissions(Permission::query()->pluck('id'));

            self::role($team, TeamRole::Recepcion->value)
                ->syncPermissions(self::permissions([
                    'ViewAny:Branch',
                    'View:Branch',
                    'ViewAny:Brand',
                    'View:Brand',
                    'ViewAny:Client',
                    'View:Client',
                    'Create:Client',
                    'Update:Client',
                    'ViewAny:MaintenanceType',
                    'View:MaintenanceType',
                    'ViewAny:Motorcycle',
                    'View:Motorcycle',
                    'Create:Motorcycle',
                    'Update:Motorcycle',
                    'ViewAny:MotorcycleModel',
                    'View:MotorcycleModel',
                    'ViewAny:InventoryProduct',
                    'View:InventoryProduct',
                    'Create:InventoryProduct',
                    'Update:InventoryProduct',
                    'ViewAny:WorkOrder',
                    'View:WorkOrder',
                    'Create:WorkOrder',
                    'Update:WorkOrder',
                ]));

            self::role($team, TeamRole::Mecanico->value)
                ->syncPermissions(self::permissions([
                    'ViewAny:Branch',
                    'View:Branch',
                    'ViewAny:Brand',
                    'View:Brand',
                    'ViewAny:Client',
                    'View:Client',
                    'ViewAny:MaintenanceType',
                    'View:MaintenanceType',
                    'ViewAny:Motorcycle',
                    'View:Motorcycle',
                    'ViewAny:MotorcycleModel',
                    'View:MotorcycleModel',
                    'ViewAny:InventoryProduct',
                    'View:InventoryProduct',
                    'ViewAny:WorkOrder',
                    'View:WorkOrder',
                    'Create:WorkOrder',
                    'Update:WorkOrder',
                ]));

            self::pruneSystemRolesFromTeam($team);
        });
    }

    public static function assignSystemSuperAdmin(User $user): void
    {
        $user->forceFill(['is_system_admin' => true])->save();
    }

    public static function isSystemSuperAdmin(User $user): bool
    {
        return (bool) $user->is_system_admin;
    }

    public static function ensureInvitableRole(Team $team, string $roleName): void
    {
        TenancyPermissions::withTeam($team, function () use ($team, $roleName): void {
            self::role($team, $roleName);
        });
    }

    /**
     * Quita roles de sistema/legado que no deben vivir dentro de un taller.
     */
    private static function pruneSystemRolesFromTeam(Team $team): void
    {
        $systemRoleNames = [
            (string) config('filament-shield.super_admin.name', 'super_admin'),
            (string) config('filament-shield.panel_user.name', 'panel_user'),
            TeamRole::Owner->value,
            TeamRole::Admin->value,
        ];

        Role::query()
            ->where('team_id', $team->getKey())
            ->whereIn('name', $systemRoleNames)
            ->get()
            ->each(function (Role $role) use ($team): void {
                self::reassignUsersToSupervisor($team, $role);
                $role->delete();
            });
    }

    private static function reassignUsersToSupervisor(Team $team, Role $role): void
    {
        TenancyPermissions::withTeam($team, function () use ($role): void {
            $supervisor = TeamRole::Supervisor->value;

            User::query()
                ->role($role->name)
                ->each(function (User $user) use ($role, $supervisor): void {
                    $user->removeRole($role->name);

                    if (! $user->hasRole($supervisor)) {
                        $user->assignRole($supervisor);
                    }
                });
        });
    }

    private static function role(Team $team, string $roleName): Role
    {
        return Role::query()->firstOrCreate(
            [
                'name' => $roleName,
                'guard_name' => 'web',
                'team_id' => $team->getKey(),
            ],
        );
    }

    /**
     * @param  list<string>  $names
     */
    private static function permissions(array $names)
    {
        return Permission::query()
            ->whereIn('name', $names)
            ->get();
    }
}
