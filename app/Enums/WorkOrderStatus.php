<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Received = 'received';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::InProgress => 'En taller',
            self::Ready => 'Listo',
            self::Delivered => 'Entregado',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [
            self::Received,
            self::InProgress,
            self::Ready,
        ];
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            self::activeCases(),
        );
    }
}
