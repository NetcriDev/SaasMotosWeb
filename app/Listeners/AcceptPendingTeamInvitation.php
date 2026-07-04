<?php

namespace App\Listeners;

use App\Services\TeamInvitationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;

class AcceptPendingTeamInvitation
{
    public function __construct(
        private readonly TeamInvitationService $invitations,
    ) {}

    public function handle(Login|Registered $event): void
    {
        $token = Session::pull('team_invitation_token');

        if (blank($token)) {
            return;
        }

        try {
            $this->invitations->accept($token, $event->user);
        } catch (\Throwable) {
            Session::put('team_invitation_token', $token);
        }
    }
}
