<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantPermissions;

class ClientPolicy
{
    use ChecksTenantPermissions;

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_client');
    }

    public function view(User $user, Client $client): bool
    {
        return $this->belongsToCurrentTeam($client) && $this->can($user, 'view_client');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'manage_client');
    }

    public function update(User $user, Client $client): bool
    {
        return $this->belongsToCurrentTeam($client) && $this->can($user, 'manage_client');
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->belongsToCurrentTeam($client) && $this->can($user, 'manage_client');
    }
}
