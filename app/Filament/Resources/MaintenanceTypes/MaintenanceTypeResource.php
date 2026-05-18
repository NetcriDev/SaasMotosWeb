<?php

namespace App\Filament\Resources\MaintenanceTypes;

use App\Filament\Resources\MaintenanceTypes\Pages\CreateMaintenanceType;
use App\Filament\Resources\MaintenanceTypes\Pages\EditMaintenanceType;
use App\Filament\Resources\MaintenanceTypes\Pages\ListMaintenanceTypes;
use App\Filament\Resources\MaintenanceTypes\Tables\MaintenanceTypesTable;
use App\Models\MaintenanceType;
use App\Support\Money;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceTypeResource extends Resource
{
    protected static ?string $model = MaintenanceType::class;

    protected static ?string $tenantRelationshipName = 'maintenanceTypes';

    protected static ?string $modelLabel = 'tipo de mantenimiento';

    protected static ?string $pluralModelLabel = 'tipos de mantenimiento';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(120),
            Textarea::make('description')
                ->label('Descripción')
                ->rows(3)
                ->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0)
                    ->prefix(Money::symbol()),
                TextInput::make('estimated_duration_minutes')
                    ->label('Duración estimada')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('min'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceTypes::route('/'),
            'create' => CreateMaintenanceType::route('/create'),
            'edit' => EditMaintenanceType::route('/{record}/edit'),
        ];
    }
}
