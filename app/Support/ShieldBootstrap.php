<?php

namespace App\Support;

use App\Enums\TeamRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;

final class ShieldBootstrap
{
    public static function assignSupervisor(User $user, Team $team): void
    {
        self::ensureDefaultTeamRoles($team);

        TenancyPermissions::assignRole($user, TeamRole::Supervisor->value, $team);
    }

    public static function assignSuperAdmin(User $user, Team $team): void
    {
        self::assignSupervisor($user, $team);
    }

    public static function ensureDefaultTeamRoles(Team $team): void
    {
        TenancyPermissions::withTeam($team, function () use ($team): void {
            self::ensurePermissionNames([
                'ViewAny:InventoryProduct',
                'View:InventoryProduct',
                'Create:InventoryProduct',
                'Update:InventoryProduct',
                'Delete:InventoryProduct',
                'DeleteAny:InventoryProduct',
            ]);

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

    /**
     * @param  list<string>  $names
     */
    private static function ensurePermissionNames(array $names): void
    {
        foreach ($names as $name) {
            Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}
