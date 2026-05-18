<?php

namespace App\Http\Controllers;

use App\Services\TeamInvitationService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcceptTeamInvitationController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->acceptAuthenticated($token);
        }

        session(['team_invitation_token' => $token]);

        return view('invitations.accept', [
            'token' => $token,
            'loginUrl' => Filament::getPanel('admin')->getLoginUrl(),
            'registerUrl' => Filament::getPanel('admin')->getRegistrationUrl(),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        if (! Auth::check()) {
            session(['team_invitation_token' => $token]);

            return redirect()->to(Filament::getPanel('admin')->getLoginUrl());
        }

        return $this->acceptAuthenticated($token);
    }

    private function acceptAuthenticated(string $token): RedirectResponse
    {
        $team = app(TeamInvitationService::class)->accept($token, Auth::user());

        session()->forget('team_invitation_token');

        $panel = Filament::getPanel('admin');

        return redirect()->to(
            $panel->getUrl($team),
        );
    }
}
