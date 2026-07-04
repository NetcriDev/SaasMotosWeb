<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use App\Support\DefaultWorkshopCatalog;
use App\Support\ShieldBootstrap;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterTeam extends RegisterTenant
{
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && ShieldBootstrap::isSystemSuperAdmin($user);
    }

    public static function getLabel(): string
    {
        return 'Registrar taller';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nombre del taller')
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);

        ShieldBootstrap::ensureDefaultTeamRoles($team);
        DefaultWorkshopCatalog::ensureForTeam($team);

        return $team;
    }
}
