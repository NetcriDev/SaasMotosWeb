<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use App\Support\ShieldBootstrap;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterTeam extends RegisterTenant
{
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

        $user = auth()->user();

        $team->members()->attach($user);

        ShieldBootstrap::assignSuperAdmin($user, $team);

        return $team;
    }
}
