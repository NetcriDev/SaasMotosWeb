<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Motorcycle;
use Illuminate\Auth\Access\HandlesAuthorization;

class MotorcyclePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Motorcycle');
    }

    public function view(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('View:Motorcycle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Motorcycle');
    }

    public function update(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('Update:Motorcycle');
    }

    public function delete(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('Delete:Motorcycle');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Motorcycle');
    }

    public function restore(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('Restore:Motorcycle');
    }

    public function forceDelete(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('ForceDelete:Motorcycle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Motorcycle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Motorcycle');
    }

    public function replicate(AuthUser $authUser, Motorcycle $motorcycle): bool
    {
        return $authUser->can('Replicate:Motorcycle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Motorcycle');
    }

}