<?php

use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\MaintenanceType;
use App\Models\Motorcycle;
use App\Models\Team;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('copies maintenance type price when the service type changes', function (): void {
    $team = Team::query()->create(['name' => 'Taller Test']);
    $branch = Branch::query()->create([
        'team_id' => $team->id,
        'name' => 'Central',
        'address' => 'Calle 1',
        'phone' => '600000000',
    ]);
    $client = Client::query()->create([
        'team_id' => $team->id,
        'branch_id' => $branch->id,
        'name' => 'Cliente',
        'email' => 'cliente@test.com',
    ]);
    $motorcycle = Motorcycle::query()->create([
        'team_id' => $team->id,
        'client_id' => $client->id,
        'branch_id' => $branch->id,
        'license_plate' => 'TEST001',
        'brand_id' => null,
        'motorcycle_model_id' => null,
    ]);

    $service = MaintenanceType::query()->create([
        'team_id' => $team->id,
        'name' => 'Aceite',
        'description' => 'Servicio demo',
        'price' => 55.50,
        'estimated_duration_minutes' => 30,
    ]);

    $order = WorkOrder::query()->create([
        'team_id' => $team->id,
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'motorcycle_id' => $motorcycle->id,
        'maintenance_type_id' => $service->id,
        'status' => WorkOrderStatus::Received,
        'received_at' => now(),
    ]);

    expect((float) $order->estimated_total)->toBe(55.5);
});
