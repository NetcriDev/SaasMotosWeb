<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array PERMISSIONS = [
        'manage_team',
        'view_branch',
        'manage_branch',
        'view_client',
        'manage_client',
        'view_motorcycle',
        'manage_motorcycle',
        'view_work_order',
        'manage_work_order',
        'delete_work_order',
        'view_catalog',
        'manage_catalog',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->syncRole(TeamRole::Owner->value, self::PERMISSIONS);

        $this->syncRole(TeamRole::Admin->value, [
            'manage_team',
            'view_branch',
            'manage_branch',
            'view_client',
            'manage_client',
            'view_motorcycle',
            'manage_motorcycle',
            'view_work_order',
            'manage_work_order',
            'delete_work_order',
            'view_catalog',
            'manage_catalog',
        ]);

        $this->syncRole(TeamRole::Recepcion->value, [
            'view_branch',
            'view_client',
            'manage_client',
            'view_motorcycle',
            'manage_motorcycle',
            'view_work_order',
            'manage_work_order',
            'view_catalog',
        ]);

        $this->syncRole(TeamRole::Mecanico->value, [
            'view_branch',
            'view_client',
            'view_motorcycle',
            'view_work_order',
            'manage_work_order',
            'view_catalog',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function syncRole(string $roleName, array $permissions): void
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
    }
}
