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
}
