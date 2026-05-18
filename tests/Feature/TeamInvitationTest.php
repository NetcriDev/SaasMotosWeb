<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamInvitationService;
use App\Support\TenancyPermissions;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('invites and accepts a team member', function (): void {
    $team = Team::query()->create(['name' => 'Taller Test']);
    $owner = User::factory()->create(['email' => 'owner@test.com']);
    $invitee = User::factory()->create(['email' => 'mecanico@test.com']);

    $team->members()->attach($owner);
    TenancyPermissions::assignRole($owner, TeamRole::Owner->value, $team);

    $invitation = app(TeamInvitationService::class)->invite(
        $team,
        'mecanico@test.com',
        TeamRole::Mecanico,
        $owner,
    );

    expect($invitation)->toBeInstanceOf(TeamInvitation::class)
        ->and($invitation->isPending())->toBeTrue();

    $acceptedTeam = app(TeamInvitationService::class)->accept($invitation->token, $invitee);

    expect($acceptedTeam->is($team))->toBeTrue()
        ->and($invitee->teams()->whereKey($team)->exists())->toBeTrue();

    TenancyPermissions::withTeam($team, function () use ($invitee): void {
        expect($invitee->hasRole(TeamRole::Mecanico->value))->toBeTrue();
    });
});

it('blocks panel access for users without a team', function (): void {
    $user = User::factory()->create();

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});
