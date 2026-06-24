<?php

use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Client;
use App\Models\MaintenanceType;
use App\Models\Motorcycle;
use App\Models\MotorcycleModel;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\ShieldBootstrap;
use App\Support\TenancyPermissions;
use Laravel\Sanctum\Sanctum;

function workshopApiFixture(): array
{
    $user = User::factory()->create();
    $mechanic = User::factory()->create([
        'name' => 'Carlos Mecanico',
        'email' => 'carlos@example.com',
    ]);
    $team = Team::query()->create(['name' => 'Taller API']);
    $otherTeam = Team::query()->create(['name' => 'Otro Taller']);
    $team->members()->attach([$user->id, $mechanic->id]);
    ShieldBootstrap::ensureInvitableRole($team, 'recepcion');
    ShieldBootstrap::ensureInvitableRole($team, 'mecanico');
    TenancyPermissions::assignRole($user, 'recepcion', $team);
    TenancyPermissions::assignRole($mechanic, 'mecanico', $team);

    $branch = Branch::query()->create([
        'team_id' => $team->id,
        'name' => 'Central',
        'address' => 'Calle 1',
        'phone' => '0999999999',
    ]);

    $brand = Brand::query()->create([
        'team_id' => $team->id,
        'name' => 'Honda',
    ]);

    $model = MotorcycleModel::query()->create([
        'team_id' => $team->id,
        'brand_id' => $brand->id,
        'name' => 'CB500F',
    ]);

    $maintenanceType = MaintenanceType::query()->create([
        'team_id' => $team->id,
        'name' => 'Cambio de aceite',
        'price' => 49.90,
        'estimated_duration_minutes' => 45,
    ]);

    $client = Client::query()->create([
        'team_id' => $team->id,
        'branch_id' => $branch->id,
        'name' => 'Ana Garcia',
        'email' => 'ana@example.com',
    ]);

    $motorcycle = Motorcycle::query()->create([
        'team_id' => $team->id,
        'client_id' => $client->id,
        'branch_id' => $branch->id,
        'license_plate' => 'ABC123',
        'brand_id' => $brand->id,
        'motorcycle_model_id' => $model->id,
        'year' => 2022,
    ]);

    $order = WorkOrder::query()->create([
        'team_id' => $team->id,
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'motorcycle_id' => $motorcycle->id,
        'maintenance_type_id' => $maintenanceType->id,
        'status' => WorkOrderStatus::Received,
        'received_at' => now(),
    ]);

    return compact('user', 'mechanic', 'team', 'otherTeam', 'branch', 'brand', 'model', 'maintenanceType', 'client', 'motorcycle', 'order');
}

it('lists tenant scoped branches and catalogs', function (): void {
    $fixture = workshopApiFixture();

    Sanctum::actingAs($fixture['user']);

    $this->getJson("/api/teams/{$fixture['team']->id}/branches")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Central');

    $this->getJson("/api/teams/{$fixture['team']->id}/brands")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Honda');

    $this->getJson("/api/teams/{$fixture['otherTeam']->id}/branches")
        ->assertForbidden();
});

it('creates clients motorcycles and work orders for the authenticated team', function (): void {
    $fixture = workshopApiFixture();

    Sanctum::actingAs($fixture['user']);

    $clientId = $this->postJson("/api/teams/{$fixture['team']->id}/clients", [
        'name' => 'Luis Perez',
        'email' => 'luis@example.com',
        'branch_id' => $fixture['branch']->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Luis Perez')
        ->json('data.id');

    $motorcycleId = $this->postJson("/api/teams/{$fixture['team']->id}/motorcycles", [
        'client_id' => $clientId,
        'branch_id' => $fixture['branch']->id,
        'license_plate' => ' xyz789 ',
        'brand_id' => $fixture['brand']->id,
        'motorcycle_model_id' => $fixture['model']->id,
        'year' => 2021,
    ])
        ->assertCreated()
        ->assertJsonPath('data.license_plate', 'XYZ789')
        ->json('data.id');

    $this->postJson("/api/teams/{$fixture['team']->id}/work-orders", [
        'branch_id' => $fixture['branch']->id,
        'client_id' => $clientId,
        'motorcycle_id' => $motorcycleId,
        'maintenance_type_id' => $fixture['maintenanceType']->id,
        'received_at' => now()->toISOString(),
        'notes' => 'Revision inicial',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', WorkOrderStatus::Received->value)
        ->assertJsonPath('data.estimated_total', '49.90');
});

it('filters work orders and exposes dashboard metrics', function (): void {
    $fixture = workshopApiFixture();

    Sanctum::actingAs($fixture['user']);

    $this->getJson("/api/teams/{$fixture['team']->id}/work-orders?active=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', WorkOrderStatus::Received->value);

    $this->patchJson("/api/teams/{$fixture['team']->id}/work-orders/{$fixture['order']->id}/status", [
        'status' => WorkOrderStatus::InProgress->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', WorkOrderStatus::InProgress->value);

    $this->getJson("/api/teams/{$fixture['team']->id}/dashboard")
        ->assertOk()
        ->assertJsonPath('stats.active_orders', 1)
        ->assertJsonPath('status_counts.in_progress', 1);
});

it('exposes mechanics for the mobile app', function (): void {
    $fixture = workshopApiFixture();

    Sanctum::actingAs($fixture['user']);

    $this->getJson("/api/teams/{$fixture['team']->id}/mechanics")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $fixture['mechanic']->id)
        ->assertJsonPath('data.0.name', 'Carlos Mecanico')
        ->assertJsonPath('data.0.roles.0', 'mecanico');
});
