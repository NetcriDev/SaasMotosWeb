<?php

namespace App\Filament\Resources\InventoryProducts;

use App\Filament\Resources\InventoryProducts\Pages\CreateInventoryProduct;
use App\Filament\Resources\InventoryProducts\Pages\EditInventoryProduct;
use App\Filament\Resources\InventoryProducts\Pages\ListInventoryProducts;
use App\Models\InventoryProduct;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class InventoryProductResource extends Resource
{
    protected static ?string $model = InventoryProduct::class;

    protected static ?string $tenantRelationshipName = 'inventoryProducts';

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'inventario';

    protected static ?string $navigationLabel = 'Inventario';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('sku')
                ->label('Codigo / SKU')
                ->maxLength(80),
            TextInput::make('name')
                ->label('Producto')
                ->required()
                ->maxLength(255),
            TextInput::make('category')
                ->label('Categoria')
                ->maxLength(120),
            TextInput::make('unit')
                ->label('Unidad')
                ->required()
                ->default('unidad')
                ->maxLength(30),
            TextInput::make('sale_price')
                ->label('Precio venta')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->default(0)
                ->prefix(Money::symbol()),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
            Repeater::make('stocks')
                ->label('Stock por sucursal')
                ->relationship()
                ->schema([
                    Select::make('branch_id')
                        ->label('Sucursal')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Stock actual')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(0)
                        ->required(),
                    TextInput::make('min_quantity')
                        ->label('Stock minimo')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(0),
                ])
                ->columns(3)
                ->defaultItems(0)
                ->addActionLabel('Agregar sucursal')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sale_price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state): string => Money::format($state))
                    ->sortable(),
                TextColumn::make('stock_total')
                    ->label('Stock total')
                    ->state(fn (InventoryProduct $record): string => number_format((float) $record->stocks()->sum('quantity'), 2)),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryProducts::route('/'),
            'create' => CreateInventoryProduct::route('/create'),
            'edit' => EditInventoryProduct::route('/{record}/edit'),
        ];
    }
}
