<?php

namespace App\Policies;

use App\Models\Motorcycle;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantPermissions;

class MotorcyclePolicy
{
    use ChecksTenantPermissions;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_motorcycle');
    }

    public function view(User $user, Motorcycle $motorcycle): bool
    {
        return $this->belongsToCurrentTeam($motorcycle) && $this->can($user, 'view_motorcycle');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'manage_motorcycle');
    }

    public function update(User $user, Motorcycle $motorcycle): bool
    {
        return $this->belongsToCurrentTeam($motorcycle) && $this->can($user, 'manage_motorcycle');
    }

    public function delete(User $user, Motorcycle $motorcycle): bool
    {
        return $this->belongsToCurrentTeam($motorcycle) && $this->can($user, 'manage_motorcycle');
    }
}
