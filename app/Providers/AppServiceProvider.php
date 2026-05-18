<?php

namespace App\Providers;

use App\Listeners\AcceptPendingTeamInvitation;
use App\Listeners\SyncPermissionsTeamId;
use App\Models\Brand;
use App\Models\MaintenanceType;
use App\Models\MotorcycleModel;
use App\Policies\CatalogPolicy;
use Filament\Events\TenantSet;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Brand::class, CatalogPolicy::class);
        Gate::policy(MotorcycleModel::class, CatalogPolicy::class);
        Gate::policy(MaintenanceType::class, CatalogPolicy::class);

        Event::listen(TenantSet::class, SyncPermissionsTeamId::class);
        Event::listen(Login::class, AcceptPendingTeamInvitation::class);
        Event::listen(Registered::class, AcceptPendingTeamInvitation::class);
    }
}
