<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Recepcion = 'recepcion';
    case Mecanico = 'mecanico';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Admin => 'Administrador',
            self::Recepcion => 'Recepción',
            self::Mecanico => 'Mecánico',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }

    /**
     * Roles that can be assigned via invitation (not owner).
     *
     * @return array<string, string>
     */
    public static function invitableOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $role): bool => $role === self::Owner)
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
