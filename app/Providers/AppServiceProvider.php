<?php

namespace App\Providers;

use App\Listeners\AcceptPendingTeamInvitation;
use App\Models\User;
use App\Support\ShieldBootstrap;
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
        Event::listen(Login::class, AcceptPendingTeamInvitation::class);
        Event::listen(Registered::class, AcceptPendingTeamInvitation::class);

        Gate::before(function ($user, string $ability): ?bool {
            if ($user instanceof User && ShieldBootstrap::isSystemSuperAdmin($user)) {
                return true;
            }

            return null;
        });
    }
}
