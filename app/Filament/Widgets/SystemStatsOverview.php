<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Models\User;
use App\Support\ShieldBootstrap;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Control del software';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && ShieldBootstrap::isSystemSuperAdmin($user);
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Talleres registrados', Team::query()->count())
                ->description('Tenants activos del sistema')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('primary'),
            Stat::make('Usuarios totales', User::query()->count())
                ->description('Incluye empleados y cuentas tecnicas')
                ->icon(Heroicon::OutlinedUsers)
                ->color('info'),
            Stat::make('Empleados de talleres', User::query()->where('is_system_admin', false)->whereHas('teams')->count())
                ->description('Usuarios asociados a un taller')
                ->icon(Heroicon::OutlinedIdentification)
                ->color('success'),
        ];
    }
}
