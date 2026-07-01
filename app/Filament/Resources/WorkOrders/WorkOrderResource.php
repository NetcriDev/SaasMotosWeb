<?php

namespace App\Filament\Resources\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrders\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\Resources\WorkOrders\Tables\WorkOrdersTable;
use App\Models\Client;
use App\Models\InventoryProduct;
use App\Models\MaintenanceType;
use App\Models\Motorcycle;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\DefaultWorkshopCatalog;
use App\Support\Money;
use App\Support\TenancyPermissions;
use App\Support\TenantWorkOrderQuery;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $tenantRelationshipName = 'workOrders';

    protected static ?string $modelLabel = 'orden de trabajo';

    protected static ?string $pluralModelLabel = 'ordenes de trabajo';

    protected static string|UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Recepcion')
                ->schema([
                    Select::make('branch_id')
                        ->label('Sucursal')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => self::currentUserBranchId())
                        ->disabled(fn (string $operation): bool => $operation === 'create' && self::currentUserBranchId() !== null)
                        ->dehydrated()
                        ->required(),
                    Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $set('motorcycle_id', null);

                            if (blank($state)) {
                                return;
                            }

                            $motorcycleIds = Motorcycle::query()
                                ->where('client_id', $state)
                                ->limit(2)
                                ->pluck('id');

                            if ($motorcycleIds->count() === 1) {
                                $set('motorcycle_id', $motorcycleIds->first());
                            }
                        })
                        ->createOptionForm(self::clientQuickCreateForm())
                        ->createOptionModalHeading('Nuevo cliente')
                        ->createOptionUsing(function (array $data, Get $get): int {
                            $tenant = Filament::getTenant();

                            abort_unless($tenant !== null, 403);

                            $data['branch_id'] ??= $get('branch_id');

                            return $tenant->clients()->create($data)->getKey();
                        }),
                    Select::make('motorcycle_id')
                        ->label('Moto')
                        ->relationship(
                            name: 'motorcycle',
                            titleAttribute: 'license_plate',
                            modifyQueryUsing: fn ($query, Get $get) => $query->where('client_id', $get('client_id')),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Motorcycle $record): string => self::motorcycleOptionLabel($record))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Get $get) => blank($get('client_id')))
                        ->createOptionForm(self::motorcycleQuickCreateForm())
                        ->createOptionModalHeading('Nueva moto del cliente')
                        ->createOptionUsing(function (array $data, Get $get): int {
                            $tenant = Filament::getTenant();
                            $clientId = $get('client_id');

                            abort_unless($tenant !== null && filled($clientId), 403);

                            abort_unless(
                                Client::query()
                                    ->whereKey($clientId)
                                    ->where('team_id', $tenant->getKey())
                                    ->exists(),
                                403,
                            );

                            $data['client_id'] = $clientId;
                            $data['branch_id'] ??= $get('branch_id');

                            return $tenant->motorcycles()->create($data)->getKey();
                        }),
                    Select::make('mechanic_id')
                        ->label('Mecanico asignado')
                        ->relationship(
                            name: 'mechanic',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => self::mechanicsQuery($query),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (User $record): string => "{$record->name} ({$record->email})",
                        )
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->default(fn (): ?int => self::defaultMechanicId())
                        ->required()
                        ->disabled(fn (): bool => self::currentUserIsMechanic())
                        ->dehydrated()
                        ->helperText(fn (): ?string => self::currentUserIsMechanic()
                            ? 'Tu usuario mecanico queda asignado automaticamente.'
                            : 'Selecciona el mecanico responsable de ejecutar el trabajo.'),
                    DateTimePicker::make('received_at')
                        ->label('Ingreso')
                        ->default(now())
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Diagnostico inicial')
                ->schema([
                    Select::make('maintenance_type_id')
                        ->label('Plan / tipo de mantenimiento')
                        ->relationship(
                            name: 'maintenanceType',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('name'),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (MaintenanceType $record): string => sprintf(
                                '%s - %s',
                                $record->name,
                                Money::format($record->price),
                            ),
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                            'estimated_total',
                            self::calculateEstimatedTotal($get('maintenance_type_id'), $get('activities'), $get('products')),
                        )),
                    Select::make('affected_systems')
                        ->label('Sistemas intervenidos')
                        ->options(fn (): array => self::motorcycleSystemOptions())
                        ->multiple()
                        ->searchable()
                        ->preload(),
                    Textarea::make('intake_reason')
                        ->label('Motivo de ingreso')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Ej: falla al encender, ruido en motor, revision por kilometraje.'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Actividades realizadas')
                ->schema([
                    Repeater::make('activities')
                        ->label('Actividades')
                        ->relationship()
                        ->schema([
                            Select::make('system')
                                ->label('Sistema')
                                ->options(fn (): array => self::motorcycleSystemOptions())
                                ->searchable()
                                ->preload(),
                            TextInput::make('description')
                                ->label('Actividad')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            TextInput::make('service_cost')
                                ->label('Costo servicio')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default(0)
                                ->prefix(Money::symbol())
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                                    '../../estimated_total',
                                    self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                )),
                            Toggle::make('is_billable')
                                ->label('Cobrar')
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                                    '../../estimated_total',
                                    self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                )),
                            Textarea::make('notes')
                                ->label('Notas')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar actividad')
                        ->reorderable(false)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                            'estimated_total',
                            self::calculateEstimatedTotal($get('maintenance_type_id'), $get('activities'), $get('products')),
                        )),
                ])
                ->columnSpanFull(),

            Section::make('Productos utilizados')
                ->schema([
                    Repeater::make('products')
                        ->label('Productos / repuestos')
                        ->relationship()
                        ->schema([
                            Hidden::make('branch_id')
                                ->default(fn (Get $get): mixed => $get('../../branch_id')),
                            Select::make('inventory_product_id')
                                ->label('Producto')
                                ->options(fn (Get $get): array => self::productOptionsForBranch($get('../../branch_id')))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    $product = filled($state) ? InventoryProduct::query()->find($state) : null;

                                    $set('unit_price', $product?->sale_price ?? 0);
                                    $set('branch_id', $get('../../branch_id'));
                                    $set(
                                        '../../estimated_total',
                                        self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                    );
                                }),
                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->default(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                                    '../../estimated_total',
                                    self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                )),
                            TextInput::make('unit_price')
                                ->label('Precio unitario')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default(0)
                                ->prefix(Money::symbol())
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                                    '../../estimated_total',
                                    self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                )),
                            Toggle::make('is_billable')
                                ->label('Cobrar')
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                                    '../../estimated_total',
                                    self::calculateEstimatedTotal($get('../../maintenance_type_id'), $get('../../activities'), $get('../../products')),
                                )),
                            Textarea::make('notes')
                                ->label('Notas')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar producto')
                        ->reorderable(false)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set(
                            'estimated_total',
                            self::calculateEstimatedTotal($get('maintenance_type_id'), $get('activities'), $get('products')),
                        )),
                ])
                ->columnSpanFull(),

            Section::make('Cobro y cierre')
                ->schema([
                    TextInput::make('estimated_total')
                        ->label('Total a cobrar')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix(Money::symbol())
                        ->readOnly()
                        ->dehydrated()
                        ->helperText('Suma el precio del plan/tipo seleccionado mas las actividades marcadas para cobrar.'),
                    Select::make('status')
                        ->label('Estado')
                        ->options(WorkOrderStatus::options())
                        ->default(WorkOrderStatus::Received->value)
                        ->required(),
                    DateTimePicker::make('completed_at')
                        ->label('Finalizacion'),
                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return WorkOrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return TenantWorkOrderQuery::make();
    }

    private static function clientQuickCreateForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->required()
                ->maxLength(255),
            Select::make('branch_id')
                ->label('Sucursal principal')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    private static function motorcycleQuickCreateForm(): array
    {
        return [
            TextInput::make('license_plate')
                ->label('Patente / placa')
                ->required()
                ->maxLength(32)
                ->helperText('Unica por taller. Se normaliza a mayusculas.')
                ->rule(static function (): \Closure {
                    return function (string $attribute, mixed $value, \Closure $fail): void {
                        $normalized = strtoupper(trim((string) $value));

                        if ($normalized === '') {
                            return;
                        }

                        $tenant = Filament::getTenant();

                        if ($tenant === null) {
                            return;
                        }

                        if (
                            Motorcycle::query()
                                ->where('team_id', $tenant->getKey())
                                ->where('license_plate', $normalized)
                                ->exists()
                        ) {
                            $fail('Ya existe una moto con esta patente en tu taller.');
                        }
                    };
                }),
            Select::make('branch_id')
                ->label('Sucursal habitual')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
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
                ->label('Anio')
                ->numeric()
                ->minValue(1900)
                ->maxValue((int) date('Y') + 1),
        ];
    }

    private static function motorcycleOptionLabel(Motorcycle $motorcycle): string
    {
        $details = collect([
            $motorcycle->brand?->name,
            $motorcycle->motorcycleModel?->name,
            $motorcycle->year,
        ])
            ->filter()
            ->implode(' ');

        return filled($details)
            ? "{$motorcycle->license_plate} - {$details}"
            : $motorcycle->license_plate;
    }

    private static function mechanicsQuery(Builder $query): Builder
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('teams', fn (Builder $query): Builder => $query->whereKey($tenant->getKey()))
            ->whereHas('roles', function (Builder $query) use ($tenant): void {
                $query
                    ->where('roles.name', 'mecanico')
                    ->where('roles.team_id', $tenant->getKey());
            })
            ->orderBy('name');
    }

    private static function motorcycleSystemOptions(): array
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return [];
        }

        DefaultWorkshopCatalog::ensureMotorcycleSystems($tenant);

        return $tenant->motorcycleSystems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }

    private static function defaultMechanicId(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User || ! self::currentUserIsMechanic()) {
            return null;
        }

        return $user->getKey();
    }

    private static function currentUserIsMechanic(): bool
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();

        if ($tenant === null || ! $user instanceof User) {
            return false;
        }

        return TenancyPermissions::withTeam(
            $tenant,
            fn (): bool => $user->hasRole('mecanico'),
        );
    }

    private static function currentUserBranchId(): ?int
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();

        if ($tenant === null || ! $user instanceof User) {
            return null;
        }

        $pivot = $user->teams()
            ->whereKey($tenant->getKey())
            ->first()
            ?->pivot;

        return filled($pivot?->branch_id) ? (int) $pivot->branch_id : null;
    }

    private static function productOptionsForBranch(mixed $branchId): array
    {
        if (blank($branchId)) {
            return [];
        }

        return InventoryProduct::query()
            ->where('team_id', Filament::getTenant()?->getKey())
            ->where('is_active', true)
            ->whereHas('stocks', fn (Builder $query): Builder => $query
                ->where('branch_id', $branchId)
                ->where('quantity', '>', 0))
            ->with(['stocks' => fn ($query) => $query->where('branch_id', $branchId)])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (InventoryProduct $product): array => [
                $product->getKey() => sprintf(
                    '%s - stock %s - %s',
                    $product->name,
                    $product->stocks->first()?->quantity ?? '0.00',
                    Money::format($product->sale_price),
                ),
            ])
            ->all();
    }

    private static function calculateEstimatedTotal(mixed $maintenanceTypeId, mixed $activities, mixed $products = []): float
    {
        $base = filled($maintenanceTypeId)
            ? (float) (MaintenanceType::query()->find($maintenanceTypeId)?->price ?? 0)
            : 0.0;

        $activitiesTotal = collect(is_array($activities) ? $activities : [])
            ->filter(fn (array $activity): bool => (bool) ($activity['is_billable'] ?? true))
            ->sum(fn (array $activity): float => (float) ($activity['service_cost'] ?? 0));
        $productsTotal = collect(is_array($products) ? $products : [])
            ->filter(fn (array $product): bool => (bool) ($product['is_billable'] ?? true))
            ->sum(fn (array $product): float => (float) ($product['quantity'] ?? 0) * (float) ($product['unit_price'] ?? 0));

        return round($base + $activitiesTotal + $productsTotal, 2);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkOrders::route('/'),
            'create' => CreateWorkOrder::route('/create'),
            'edit' => EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
