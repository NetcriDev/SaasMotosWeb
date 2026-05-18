<?php

namespace App\Listeners;

use App\Models\Team;
use App\Support\TenancyPermissions;
use Filament\Events\TenantSet;

class SyncPermissionsTeamId
{
    public function handle(TenantSet $event): void
    {
        $tenant = $event->getTenant();

        TenancyPermissions::setTeam($tenant instanceof Team ? $tenant : null);
    }
}
