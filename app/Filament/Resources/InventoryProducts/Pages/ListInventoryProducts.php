<?php

namespace App\Filament\Resources\InventoryProducts\Pages;

use App\Filament\Resources\InventoryProducts\InventoryProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryProducts extends ListRecords
{
    protected static string $resource = InventoryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
