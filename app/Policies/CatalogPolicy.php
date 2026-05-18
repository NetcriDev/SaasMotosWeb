<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksTenantPermissions;

/**
 * Política compartida para marcas, modelos y tipos de mantenimiento.
 */
class CatalogPolicy
{
    use ChecksTenantPermissions;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_catalog');
    }

    public function view(User $user, object $model): bool
    {
        return $this->belongsToCurrentTeam($model) && $this->can($user, 'view_catalog');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'manage_catalog');
    }

    public function update(User $user, object $model): bool
    {
        return $this->belongsToCurrentTeam($model) && $this->can($user, 'manage_catalog');
    }

    public function delete(User $user, object $model): bool
    {
        return $this->belongsToCurrentTeam($model) && $this->can($user, 'manage_catalog');
    }
}
