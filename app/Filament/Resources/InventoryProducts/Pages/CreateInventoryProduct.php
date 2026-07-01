<?php

namespace App\Filament\Resources\InventoryProducts\Pages;

use App\Filament\Resources\InventoryProducts\InventoryProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryProduct extends CreateRecord
{
    protected static string $resource = InventoryProductResource::class;
}
