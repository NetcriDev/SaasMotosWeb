<?php

use App\Enums\MotorcycleSystem;
use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Client;
use App\Models\InventoryProduct;
use App\Models\MaintenanceType;
use App\Models\Motorcycle;
use App\Models\MotorcycleModel;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\DefaultWorkshopCatalog;
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
    DefaultWorkshopCatalog::ensureForTeam($team);
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
        'mechanic_id' => $mechanic->id,
        'maintenance_type_id' => $maintenanceType->id,
        'intake_reason' => 'Falla al encender.',
        'affected_systems' => [MotorcycleSystem::Electrical->value],
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
        'mechanic_id' => $fixture['mechanic']->id,
        'maintenance_type_id' => $fixture['maintenanceType']->id,
        'intake_reason' => 'Revision por kilometraje y luces delanteras.',
        'affected_systems' => [MotorcycleSystem::Engine->value, MotorcycleSystem::Electrical->value],
        'activities' => [
            [
                'system' => MotorcycleSystem::Engine->value,
                'description' => 'Revision incluida en mantenimiento',
                'service_cost' => 0,
                'is_billable' => false,
            ],
            [
                'system' => MotorcycleSystem::Electrical->value,
                'description' => 'Cambio de luces',
                'service_cost' => 5,
                'is_billable' => true,
            ],
        ],
        'received_at' => now()->toISOString(),
        'notes' => 'Revision inicial',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', WorkOrderStatus::Received->value)
        ->assertJsonPath('data.mechanic_id', $fixture['mechanic']->id)
        ->assertJsonPath('data.affected_systems.1', MotorcycleSystem::Electrical->value)
        ->assertJsonPath('data.activities.1.description', 'Cambio de luces')
        ->assertJsonPath('data.estimated_total', '54.90');
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

it('discounts branch stock when a product is consumed in a work order', function (): void {
    $fixture = workshopApiFixture();

    $product = InventoryProduct::query()->create([
        'team_id' => $fixture['team']->id,
        'sku' => 'FOCO-H4',
        'name' => 'Foco delantero H4',
        'category' => 'Electricos',
        'unit' => 'unidad',
        'sale_price' => 5,
        'is_active' => true,
    ]);

    $stock = $product->stocks()->create([
        'branch_id' => $fixture['branch']->id,
        'quantity' => 3,
        'min_quantity' => 1,
    ]);

    $fixture['order']->products()->create([
        'inventory_product_id' => $product->id,
        'branch_id' => $fixture['branch']->id,
        'quantity' => 2,
        'unit_price' => 5,
        'is_billable' => true,
    ]);

    expect($stock->refresh()->quantity)->toBe('1.00')
        ->and($fixture['order']->refresh()->estimated_total)->toBe('59.90');
});
