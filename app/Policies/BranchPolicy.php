<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantPermissions;

class BranchPolicy
{
    use ChecksTenantPermissions;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_branch');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->belongsToCurrentTeam($branch) && $this->can($user, 'view_branch');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'manage_branch');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $this->belongsToCurrentTeam($branch) && $this->can($user, 'manage_branch');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $this->belongsToCurrentTeam($branch) && $this->can($user, 'manage_branch');
    }
}
