<?php

namespace App\Http\Middleware;

use App\Support\TenancyPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamId
{
    public function handle(Request $request, Closure $next): Response
    {
        TenancyPermissions::setTeamFromFilament();

        return $next($request);
    }
}
