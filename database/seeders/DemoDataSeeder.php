<?php

namespace Database\Seeders;

use App\Enums\MotorcycleSystem;
use App\Enums\TeamRole;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@taller.demo'],
            [
                'name' => 'Administrador Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $team = Team::query()->updateOrCreate(
            ['name' => 'Taller Demo Motos'],
        );

        $admin->teams()->syncWithoutDetaching([$team->id]);

        ShieldBootstrap::assignSystemSuperAdmin($admin);
        ShieldBootstrap::assignSupervisor($admin, $team);

        $receptionist = User::query()->updateOrCreate(
            ['email' => 'recepcion@taller.demo'],
            [
                'name' => 'Recepcion Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $mechanic = User::query()->updateOrCreate(
            ['email' => 'mecanico@taller.demo'],
            [
                'name' => 'Mecanico Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $team->members()->syncWithoutDetaching([$receptionist->id, $mechanic->id]);
        TenancyPermissions::assignRole($receptionist, TeamRole::Recepcion->value, $team);
        TenancyPermissions::assignRole($mechanic, TeamRole::Mecanico->value, $team);
        DefaultWorkshopCatalog::ensureForTeam($team);

        $branches = $this->seedBranches($team);
        $this->assignTeamBranches($team, $branches, [
            $admin->id => 'centro',
            $receptionist->id => 'centro',
            $mechanic->id => 'norte',
        ]);
        $this->seedInventory($team, $branches);
        $catalogs = $this->seedCatalogs($team);
        $clients = $this->seedClients($team, $branches);
        $motorcycles = $this->seedMotorcycles($team, $branches, $clients, $catalogs);
        $this->seedWorkOrders($team, $branches, $clients, $motorcycles, $catalogs['maintenanceTypes'], $mechanic);
    }

    /**
     * @return array{centro: Branch, norte: Branch}
     */
    private function seedBranches(Team $team): array
    {
        $centro = Branch::query()->updateOrCreate(
            ['team_id' => $team->id, 'name' => 'Sucursal Centro'],
            [
                'address' => 'Av. Principal 120, Local 3',
                'phone' => '+34 911 000 101',
            ],
        );

        $norte = Branch::query()->updateOrCreate(
            ['team_id' => $team->id, 'name' => 'Sucursal Norte'],
            [
                'address' => 'Calle Norte 45',
                'phone' => '+34 911 000 102',
            ],
        );

        return ['centro' => $centro, 'norte' => $norte];
    }

    /**
     * @param  array{centro: Branch, norte: Branch}  $branches
     * @param  array<int, string>  $assignments
     */
    private function assignTeamBranches(Team $team, array $branches, array $assignments): void
    {
        foreach ($assignments as $userId => $branchKey) {
            if (! isset($branches[$branchKey])) {
                continue;
            }

            $team->members()->syncWithoutDetaching([
                $userId => ['branch_id' => $branches[$branchKey]->id],
            ]);
        }
    }

    /**
     * @param  array{centro: Branch, norte: Branch}  $branches
     */
    private function seedInventory(Team $team, array $branches): void
    {
        $products = [
            [
                'sku' => 'ACE-10W40',
                'name' => 'Aceite 10W40 1L',
                'category' => 'Lubricantes',
                'unit' => 'litro',
                'sale_price' => 12.50,
                'stock' => ['centro' => 20, 'norte' => 15],
            ],
            [
                'sku' => 'FOCO-H4',
                'name' => 'Foco delantero H4',
                'category' => 'Electricos',
                'unit' => 'unidad',
                'sale_price' => 5.00,
                'stock' => ['centro' => 12, 'norte' => 8],
            ],
            [
                'sku' => 'PAST-FD',
                'name' => 'Pastillas de freno delantero',
                'category' => 'Frenos',
                'unit' => 'juego',
                'sale_price' => 28.00,
                'stock' => ['centro' => 6, 'norte' => 4],
            ],
        ];

        foreach ($products as $data) {
            $product = InventoryProduct::query()->updateOrCreate(
                ['team_id' => $team->id, 'sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                    'sale_price' => $data['sale_price'],
                    'is_active' => true,
                ],
            );

            foreach ($data['stock'] as $branchKey => $quantity) {
                $product->stocks()->updateOrCreate(
                    ['branch_id' => $branches[$branchKey]->id],
                    [
                        'quantity' => $quantity,
                        'min_quantity' => 2,
                    ],
                );
            }
        }
    }

    /**
     * @return array{
     *     brands: array<string, Brand>,
     *     models: array<string, MotorcycleModel>,
     *     maintenanceTypes: array<string, MaintenanceType>
     * }
     */
    private function seedCatalogs(Team $team): array
    {
        $brandData = [
            'honda' => 'Honda',
            'yamaha' => 'Yamaha',
            'kawasaki' => 'Kawasaki',
        ];

        $brands = [];
        foreach ($brandData as $key => $name) {
            $brands[$key] = Brand::query()->updateOrCreate(
                ['team_id' => $team->id, 'name' => $name],
            );
        }

        $modelData = [
            'cb500f' => ['brand' => 'honda', 'name' => 'CB500F'],
            'cbr600' => ['brand' => 'honda', 'name' => 'CBR600RR'],
            'mt07' => ['brand' => 'yamaha', 'name' => 'MT-07'],
            'yzf_r3' => ['brand' => 'yamaha', 'name' => 'YZF-R3'],
            'ninja400' => ['brand' => 'kawasaki', 'name' => 'Ninja 400'],
        ];

        $models = [];
        foreach ($modelData as $key => $data) {
            $models[$key] = MotorcycleModel::query()->updateOrCreate(
                [
                    'team_id' => $team->id,
                    'brand_id' => $brands[$data['brand']]->id,
                    'name' => $data['name'],
                ],
            );
        }

        $maintenanceData = [
            'aceite' => [
                'name' => 'Cambio de aceite',
                'description' => 'Aceite de motor y filtro.',
                'price' => 49.90,
                'estimated_duration_minutes' => 45,
            ],
            'revision' => [
                'name' => 'Revisión general',
                'description' => 'Inspección de seguridad y ajustes básicos.',
                'price' => 79.00,
                'estimated_duration_minutes' => 90,
            ],
            'frenos' => [
                'name' => 'Mantenimiento de frenos',
                'description' => 'Pastillas, líquido y revisión de discos.',
                'price' => 119.50,
                'estimated_duration_minutes' => 120,
            ],
            'neumaticos' => [
                'name' => 'Cambio de neumáticos',
                'description' => 'Montaje, equilibrado y presión.',
                'price' => 149.00,
                'estimated_duration_minutes' => 60,
            ],
            'electrico' => [
                'name' => 'Reparación eléctrica',
                'description' => 'Batería, alternador, luces y cableado.',
                'price' => 95.00,
                'estimated_duration_minutes' => 180,
            ],
        ];

        $maintenanceTypes = [];
        foreach ($maintenanceData as $key => $data) {
            $maintenanceTypes[$key] = MaintenanceType::query()->updateOrCreate(
                ['team_id' => $team->id, 'name' => $data['name']],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'estimated_duration_minutes' => $data['estimated_duration_minutes'],
                ],
            );
        }

        return [
            'brands' => $brands,
            'models' => $models,
            'maintenanceTypes' => $maintenanceTypes,
        ];
    }

    /**
     * @param  array{centro: Branch, norte: Branch}  $branches
     * @return array<string, Client>
     */
    private function seedClients(Team $team, array $branches): array
    {
        $clientData = [
            'ana' => [
                'name' => 'Ana García',
                'email' => 'ana.garcia@ejemplo.com',
                'branch_id' => $branches['centro']->id,
            ],
            'luis' => [
                'name' => 'Luis Martínez',
                'email' => 'luis.martinez@ejemplo.com',
                'branch_id' => $branches['centro']->id,
            ],
            'carmen' => [
                'name' => 'Carmen López',
                'email' => 'carmen.lopez@ejemplo.com',
                'branch_id' => $branches['norte']->id,
            ],
            'pedro' => [
                'name' => 'Pedro Sánchez',
                'email' => 'pedro.sanchez@ejemplo.com',
                'branch_id' => $branches['norte']->id,
            ],
        ];

        $clients = [];
        foreach ($clientData as $key => $data) {
            $clients[$key] = Client::query()->updateOrCreate(
                ['team_id' => $team->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'branch_id' => $data['branch_id'],
                ],
            );
        }

        return $clients;
    }

    /**
     * @param  array{centro: Branch, norte: Branch}  $branches
     * @param  array<string, Client>  $clients
     * @param  array{
     *     brands: array<string, Brand>,
     *     models: array<string, MotorcycleModel>,
     *     maintenanceTypes: array<string, MaintenanceType>
     * }  $catalogs
     * @return array<string, Motorcycle>
     */
    private function seedMotorcycles(
        Team $team,
        array $branches,
        array $clients,
        array $catalogs,
    ): array {
        $motorcycleData = [
            'ana_cb500' => [
                'client' => 'ana',
                'branch' => 'centro',
                'license_plate' => '1234ABC',
                'brand' => 'honda',
                'model' => 'cb500f',
                'year' => 2021,
            ],
            'luis_mt07' => [
                'client' => 'luis',
                'branch' => 'centro',
                'license_plate' => '5678DEF',
                'brand' => 'yamaha',
                'model' => 'mt07',
                'year' => 2019,
            ],
            'luis_cbr' => [
                'client' => 'luis',
                'branch' => 'centro',
                'license_plate' => '9012GHI',
                'brand' => 'honda',
                'model' => 'cbr600',
                'year' => 2017,
            ],
            'carmen_r3' => [
                'client' => 'carmen',
                'branch' => 'norte',
                'license_plate' => '3456JKL',
                'brand' => 'yamaha',
                'model' => 'yzf_r3',
                'year' => 2022,
            ],
            'pedro_ninja' => [
                'client' => 'pedro',
                'branch' => 'norte',
                'license_plate' => '7890MNO',
                'brand' => 'kawasaki',
                'model' => 'ninja400',
                'year' => 2020,
            ],
        ];

        $motorcycles = [];
        foreach ($motorcycleData as $key => $data) {
            $motorcycles[$key] = Motorcycle::query()->updateOrCreate(
                ['team_id' => $team->id, 'license_plate' => $data['license_plate']],
                [
                    'client_id' => $clients[$data['client']]->id,
                    'branch_id' => $branches[$data['branch']]->id,
                    'brand_id' => $catalogs['brands'][$data['brand']]->id,
                    'motorcycle_model_id' => $catalogs['models'][$data['model']]->id,
                    'year' => $data['year'],
                ],
            );
        }

        return $motorcycles;
    }

    /**
     * @param  array{centro: Branch, norte: Branch}  $branches
     * @param  array<string, Client>  $clients
     * @param  array<string, Motorcycle>  $motorcycles
     * @param  array<string, MaintenanceType>  $maintenanceTypes
     */
    private function seedWorkOrders(
        Team $team,
        array $branches,
        array $clients,
        array $motorcycles,
        array $maintenanceTypes,
        User $mechanic,
    ): void {
        $orders = [
            [
                'branch' => 'centro',
                'client' => 'ana',
                'motorcycle' => 'ana_cb500',
                'maintenance' => 'aceite',
                'status' => WorkOrderStatus::InProgress,
                'received_at' => Carbon::parse('2026-05-12 09:30:00'),
                'completed_at' => null,
                'notes' => 'Cliente solicita revisión de niveles.',
            ],
            [
                'branch' => 'centro',
                'client' => 'luis',
                'motorcycle' => 'luis_mt07',
                'maintenance' => 'frenos',
                'status' => WorkOrderStatus::Received,
                'received_at' => Carbon::parse('2026-05-15 11:00:00'),
                'completed_at' => null,
                'notes' => null,
            ],
            [
                'branch' => 'centro',
                'client' => 'luis',
                'motorcycle' => 'luis_cbr',
                'maintenance' => 'revision',
                'status' => WorkOrderStatus::Ready,
                'received_at' => Carbon::parse('2026-05-10 08:15:00'),
                'completed_at' => null,
                'notes' => 'Pendiente de aviso al cliente.',
            ],
            [
                'branch' => 'norte',
                'client' => 'carmen',
                'motorcycle' => 'carmen_r3',
                'maintenance' => 'neumaticos',
                'status' => WorkOrderStatus::Delivered,
                'received_at' => Carbon::parse('2026-05-01 10:00:00'),
                'completed_at' => Carbon::parse('2026-05-03 17:30:00'),
                'notes' => 'Entregada sin incidencias.',
            ],
            [
                'branch' => 'norte',
                'client' => 'pedro',
                'motorcycle' => 'pedro_ninja',
                'maintenance' => 'electrico',
                'status' => WorkOrderStatus::InProgress,
                'received_at' => Carbon::parse('2026-05-14 16:45:00'),
                'completed_at' => null,
                'notes' => 'Fallo intermitente en arranque.',
            ],
        ];

        foreach ($orders as $order) {
            $maintenanceType = $maintenanceTypes[$order['maintenance']];

            $workOrder = WorkOrder::query()->updateOrCreate(
                [
                    'team_id' => $team->id,
                    'motorcycle_id' => $motorcycles[$order['motorcycle']]->id,
                    'received_at' => $order['received_at'],
                ],
                [
                    'branch_id' => $branches[$order['branch']]->id,
                    'client_id' => $clients[$order['client']]->id,
                    'maintenance_type_id' => $maintenanceType->id,
                    'mechanic_id' => $mechanic->id,
                    'estimated_total' => $maintenanceType->price,
                    'intake_reason' => $this->intakeReason($order['maintenance']),
                    'affected_systems' => $this->affectedSystems($order['maintenance']),
                    'status' => $order['status'],
                    'completed_at' => $order['completed_at'],
                    'notes' => $order['notes'],
                ],
            );

            $workOrder->activities()->delete();
            $workOrder->activities()->createMany($this->activities($order['maintenance']));
            $workOrder->recalculateEstimatedTotal();
        }
    }

    /**
     * @return list<string>
     */
    private function affectedSystems(string $maintenance): array
    {
        return match ($maintenance) {
            'aceite', 'revision' => [MotorcycleSystem::Engine->value, MotorcycleSystem::General->value],
            'frenos' => [MotorcycleSystem::Brakes->value],
            'neumaticos' => [MotorcycleSystem::Tires->value],
            'electrico' => [MotorcycleSystem::Electrical->value],
            default => [MotorcycleSystem::General->value],
        };
    }

    private function intakeReason(string $maintenance): string
    {
        return match ($maintenance) {
            'aceite' => 'Revision por kilometraje y cambio de aceite.',
            'revision' => 'Mantenimiento programado por kilometraje.',
            'frenos' => 'Ruido o baja respuesta en sistema de frenos.',
            'neumaticos' => 'Cambio de neumaticos solicitado por desgaste.',
            'electrico' => 'Fallo intermitente en arranque o luces.',
            default => 'Ingreso para revision general.',
        };
    }

    /**
     * @return list<array{system: string, description: string, service_cost: float|int, is_billable: bool}>
     */
    private function activities(string $maintenance): array
    {
        return match ($maintenance) {
            'aceite' => [
                ['system' => MotorcycleSystem::Engine->value, 'description' => 'Cambio de aceite', 'service_cost' => 0, 'is_billable' => false],
                ['system' => MotorcycleSystem::General->value, 'description' => 'Revision de niveles', 'service_cost' => 10, 'is_billable' => true],
            ],
            'revision' => [
                ['system' => MotorcycleSystem::General->value, 'description' => 'Revision incluida en plan', 'service_cost' => 0, 'is_billable' => false],
            ],
            'frenos' => [
                ['system' => MotorcycleSystem::Brakes->value, 'description' => 'Diagnostico de frenos', 'service_cost' => 15, 'is_billable' => true],
            ],
            'neumaticos' => [
                ['system' => MotorcycleSystem::Tires->value, 'description' => 'Montaje y balanceo', 'service_cost' => 20, 'is_billable' => true],
            ],
            'electrico' => [
                ['system' => MotorcycleSystem::Electrical->value, 'description' => 'Revision de sistema electrico', 'service_cost' => 30, 'is_billable' => true],
            ],
            default => [],
        };
    }
}
