<?php

namespace App\Enums;

enum MotorcycleSystem: string
{
    case Engine = 'engine';
    case Electrical = 'electrical';
    case Transmission = 'transmission';
    case Brakes = 'brakes';
    case Suspension = 'suspension';
    case Fuel = 'fuel';
    case Cooling = 'cooling';
    case Tires = 'tires';
    case Bodywork = 'bodywork';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Engine => 'Sistema motor',
            self::Electrical => 'Sistema electrico',
            self::Transmission => 'Transmision',
            self::Brakes => 'Frenos',
            self::Suspension => 'Suspension',
            self::Fuel => 'Combustible',
            self::Cooling => 'Refrigeracion',
            self::Tires => 'Llantas / neumaticos',
            self::Bodywork => 'Carroceria',
            self::General => 'Revision general',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $system): array => [$system->value => $system->label()])
            ->all();
    }
}
