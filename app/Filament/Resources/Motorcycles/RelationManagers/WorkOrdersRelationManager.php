<?php

namespace App\Filament\Resources\Motorcycles\RelationManagers;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Support\WorkOrderHistoryTable;
use App\Models\Motorcycle;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $relatedResource = WorkOrderResource::class;

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedClipboardDocumentList;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Órdenes de trabajo';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Motorcycle) {
            return null;
        }

        return (string) $ownerRecord->workOrders()->count();
    }

    public function table(Table $table): Table
    {
        return WorkOrderHistoryTable::configure(
            $table->heading('Historial de servicios'),
            showClient: false,
            showMotorcycle: false,
        )->headerActions([
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    /** @var Motorcycle $motorcycle */
                    $motorcycle = $this->getOwnerRecord();

                    return [
                        ...$data,
                        'motorcycle_id' => $motorcycle->getKey(),
                        'client_id' => $motorcycle->client_id,
                        'branch_id' => $data['branch_id'] ?? $motorcycle->branch_id,
                    ];
                }),
        ]);
    }
}
