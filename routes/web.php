<?php

use App\Http\Controllers\AcceptTeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invitacion/{token}', [AcceptTeamInvitationController::class, 'show'])
    ->name('team-invitations.show');

Route::post('/invitacion/{token}', [AcceptTeamInvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('team-invitations.accept');
