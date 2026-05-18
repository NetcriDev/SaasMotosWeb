<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\Concerns\ChecksTenantPermissions;

class WorkOrderPolicy
{
    use ChecksTenantPermissions;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_work_order');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $this->belongsToCurrentTeam($workOrder) && $this->can($user, 'view_work_order');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'manage_work_order');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->belongsToCurrentTeam($workOrder) && $this->can($user, 'manage_work_order');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $this->belongsToCurrentTeam($workOrder) && $this->can($user, 'delete_work_order');
    }
}
