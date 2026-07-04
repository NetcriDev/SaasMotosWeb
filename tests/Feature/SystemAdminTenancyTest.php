<?php

use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

it('lets the system admin access the panel and all workshops without being a member', function (): void {
    $team = Team::query()->create(['name' => 'Taller Norte']);
    $systemAdmin = User::factory()->create(['is_system_admin' => true]);

    expect($systemAdmin->teams()->exists())->toBeFalse()
        ->and($systemAdmin->canAccessPanel(filament()->getPanel('admin')))->toBeTrue()
        ->and($systemAdmin->canAccessTenant($team))->toBeTrue()
        ->and($systemAdmin->getTenants(filament()->getPanel('admin'))->pluck('id')->all())->toBe([$team->id]);
});

it('keeps users without a workshop or system admin access out of the panel', function (): void {
    $user = User::factory()->create(['is_system_admin' => false]);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

it('registers a workshop without adding the system admin as an employee', function (): void {
    $systemAdmin = User::factory()->create(['is_system_admin' => true]);

    $this->actingAs($systemAdmin);

    expect(RegisterTeam::canView())->toBeTrue();

    $team = (new TestRegisterTeamPage())->registerWorkshop(['name' => 'Taller Sur']);

    expect($team)->toBeInstanceOf(Model::class)
        ->and($team->name)->toBe('Taller Sur')
        ->and($systemAdmin->teams()->whereKey($team->getKey())->exists())->toBeFalse();
});

class TestRegisterTeamPage extends RegisterTeam
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function registerWorkshop(array $data): Team
    {
        return $this->handleRegistration($data);
    }
}
