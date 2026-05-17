<?php

namespace App\Filament\Resources\Motorcycles;

use App\Filament\Resources\Motorcycles\Pages\CreateMotorcycle;
use App\Filament\Resources\Motorcycles\Pages\EditMotorcycle;
use App\Filament\Resources\Motorcycles\Pages\ListMotorcycles;
use App\Filament\Resources\Motorcycles\Tables\MotorcyclesTable;
use App\Models\Motorcycle;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MotorcycleResource extends Resource
{
    protected static ?string $model = Motorcycle::class;

    protected static ?string $tenantRelationshipName = 'motorcycles';

    protected static ?string $modelLabel = 'moto';

    protected static ?string $pluralModelLabel = 'motos';

    protected static string|UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'license_plate';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('branch_id')
                ->label('Sucursal habitual')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('license_plate')
                ->label('Patente / placa')
                ->required()
                ->maxLength(32)
                ->helperText('Única por taller. Se normaliza a mayúsculas.')
                ->rule(static function (Field $component): \Closure {
                    return function (string $attribute, mixed $value, \Closure $fail) use ($component): void {
                        $normalized = strtoupper(trim((string) $value));
                        if ($normalized === '') {
                            return;
                        }

                        $tenant = Filament::getTenant();
                        if (! $tenant) {
                            return;
                        }

                        $query = Motorcycle::query()
                            ->where('team_id', $tenant->getKey())
                            ->where('license_plate', $normalized);

                        $record = $component->getRecord();
                        if ($record instanceof Motorcycle && $record->exists) {
                            $query->whereKeyNot($record->getKey());
                        }

                        if ($query->exists()) {
                            $fail('Ya existe una moto con esta patente en tu taller. Edítala desde el listado o usa otra patente.');
                        }
                    };
                }),
            Select::make('brand_id')
                ->label('Marca')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('motorcycle_model_id', null)),
            Select::make('motorcycle_model_id')
                ->label('Modelo')
                ->relationship(
                    name: 'motorcycleModel',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query, Get $get) => $query->where('brand_id', $get('brand_id')),
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (Get $get) => blank($get('brand_id'))),
            TextInput::make('year')
                ->label('Año')
                ->numeric()
                ->minValue(1900)
                ->maxValue((int) date('Y') + 1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return MotorcyclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMotorcycles::route('/'),
            'create' => CreateMotorcycle::route('/create'),
            'edit' => EditMotorcycle::route('/{record}/edit'),
        ];
    }
}
