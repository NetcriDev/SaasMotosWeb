<?php

namespace App\Support;

use App\Enums\MotorcycleSystem as MotorcycleSystemEnum;
use App\Models\MaintenanceType;
use App\Models\Team;

final class DefaultWorkshopCatalog
{
    public static function ensureForTeam(Team $team): void
    {
        self::ensureMotorcycleSystems($team);
        self::ensureMaintenanceTypes($team);
    }

    public static function ensureMotorcycleSystems(Team $team): void
    {
        foreach (MotorcycleSystemEnum::cases() as $index => $system) {
            $team->motorcycleSystems()->updateOrCreate(
                ['code' => $system->value],
                [
                    'name' => $system->label(),
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }

    public static function ensureMaintenanceTypes(Team $team): void
    {
        foreach (self::maintenanceTypes() as $data) {
            MaintenanceType::query()->updateOrCreate(
                [
                    'team_id' => $team->getKey(),
                    'name' => $data['name'],
                ],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'estimated_duration_minutes' => $data['estimated_duration_minutes'],
                ],
            );
        }
    }

    /**
     * @return list<array{name: string, description: string, price: float, estimated_duration_minutes: int|null}>
     */
    public static function maintenanceTypes(): array
    {
        return [
            [
                'name' => 'Mantenimiento normal',
                'description' => 'Servicio base para revision y mantenimiento regular de la moto.',
                'price' => 0.00,
                'estimated_duration_minutes' => 60,
            ],
            [
                'name' => 'Mantenimiento plus',
                'description' => 'Plan premium con mantenimientos incluidos segun kilometraje recomendado por la marca.',
                'price' => 150.00,
                'estimated_duration_minutes' => 120,
            ],
            [
                'name' => 'Garantia / servicio incluido',
                'description' => 'Servicio cubierto por garantia o plan adquirido; solo se cobran actividades no incluidas.',
                'price' => 0.00,
                'estimated_duration_minutes' => 90,
            ],
        ];
    }
}
