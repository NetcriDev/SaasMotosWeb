<?php

namespace App\Support;

use App\Models\WorkOrder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

final class TenantWorkOrderQuery
{
    public static function make(): Builder
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return WorkOrder::query()->whereRaw('0 = 1');
        }

        return WorkOrder::query()->whereBelongsTo($tenant, 'team');
    }
}
