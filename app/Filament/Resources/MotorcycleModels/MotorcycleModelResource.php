<?php

namespace App\Filament\Resources\MotorcycleModels;

use App\Filament\Resources\MotorcycleModels\Pages\CreateMotorcycleModel;
use App\Filament\Resources\MotorcycleModels\Pages\EditMotorcycleModel;
use App\Filament\Resources\MotorcycleModels\Pages\ListMotorcycleModels;
use App\Filament\Resources\MotorcycleModels\Tables\MotorcycleModelsTable;
use App\Models\MotorcycleModel;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MotorcycleModelResource extends Resource
{
    protected static ?string $model = MotorcycleModel::class;

    protected static ?string $tenantRelationshipName = 'motorcycleModels';

    protected static ?string $modelLabel = 'modelo';

    protected static ?string $pluralModelLabel = 'modelos';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('brand_id')
                ->label('Marca')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')
                ->label('Nombre del modelo')
                ->required()
                ->maxLength(120),
        ]);
    }

    public static function table(Table $table): Table
    {
        return MotorcycleModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMotorcycleModels::route('/'),
            'create' => CreateMotorcycleModel::route('/create'),
            'edit' => EditMotorcycleModel::route('/{record}/edit'),
        ];
    }
}
