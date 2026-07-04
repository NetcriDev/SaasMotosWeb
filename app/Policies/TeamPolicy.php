<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ShieldBootstrap;

class TeamPolicy
{
    public function create(User $user): bool
    {
        return ShieldBootstrap::isSystemSuperAdmin($user);
    }
}
