<?php

namespace App\Filament\Resources\InventoryProducts\Pages;

use App\Filament\Resources\InventoryProducts\InventoryProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryProduct extends EditRecord
{
    protected static string $resource = InventoryProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
