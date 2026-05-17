<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterTeam extends RegisterTenant
{

    public static function getLabel(): string
    {
        return 'Register Team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required(),
            ]);
    }



    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);
        
        $team->members()->attach(auth()->user());
        return $team;
    }
}