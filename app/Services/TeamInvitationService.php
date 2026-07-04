<?php

namespace App\Services;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\TenancyPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamInvitationService
{
    public function invite(Team $team, string $email, TeamRole $role, User $inviter): TeamInvitation
    {
        $email = strtolower(trim($email));

        if (! array_key_exists($role->value, TeamRole::invitableOptions())) {
            throw ValidationException::withMessages([
                'role' => 'Solo puedes invitar con rol de recepcionista o mecanico.',
            ]);
        }

        if ($team->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Ese usuario ya pertenece al taller.',
            ]);
        }

        return TeamInvitation::query()->updateOrCreate(
            [
                'team_id' => $team->getKey(),
                'email' => $email,
            ],
            [
                'role' => $role,
                'token' => TeamInvitation::generateToken(),
                'expires_at' => now()->addDays(7),
                'invited_by' => $inviter->getKey(),
                'accepted_at' => null,
            ],
        );
    }

    public function accept(string $token, User $user): Team
    {
        $invitation = TeamInvitation::query()
            ->where('token', $token)
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => 'La invitación no es válida.',
            ]);
        }

        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'token' => 'Esta invitación ya fue utilizada.',
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => 'Esta invitación ha expirado.',
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'Debes iniciar sesión con el correo invitado: '.$invitation->email,
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): Team {
            $team = $invitation->team;

            $team->members()->syncWithoutDetaching([$user->getKey()]);

            TenancyPermissions::assignRole($user, $invitation->role->value, $team);

            $invitation->update(['accepted_at' => now()]);

            return $team;
        });
    }
}
