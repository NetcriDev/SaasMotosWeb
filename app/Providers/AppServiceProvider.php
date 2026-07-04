<?php

namespace App\Providers;

use App\Listeners\AcceptPendingTeamInvitation;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
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
    }
}
