<?php

namespace App\Policies\Concerns;

use App\Models\Team;
use App\Models\User;
use App\Support\TenancyPermissions;
use Filament\Facades\Filament;

trait ChecksTenantPermissions
{
    protected function belongsToCurrentTeam(object $model): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Team || ! isset($model->team_id)) {
            return false;
        }

        return (int) $model->team_id === (int) $tenant->getKey();
    }

    protected function can(User $user, string $permission): bool
    {
        return TenancyPermissions::userHasPermission($user, $permission);
    }
}
