<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MotorcycleModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class MotorcycleModelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MotorcycleModel');
    }

    public function view(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('View:MotorcycleModel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MotorcycleModel');
    }

    public function update(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('Update:MotorcycleModel');
    }

    public function delete(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('Delete:MotorcycleModel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MotorcycleModel');
    }

    public function restore(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('Restore:MotorcycleModel');
    }

    public function forceDelete(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('ForceDelete:MotorcycleModel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MotorcycleModel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MotorcycleModel');
    }

    public function replicate(AuthUser $authUser, MotorcycleModel $motorcycleModel): bool
    {
        return $authUser->can('Replicate:MotorcycleModel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MotorcycleModel');
    }

}