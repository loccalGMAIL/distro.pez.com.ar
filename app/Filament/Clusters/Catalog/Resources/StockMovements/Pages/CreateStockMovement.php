<?php

namespace App\Filament\Clusters\Catalog\Resources\StockMovements\Pages;

use App\Filament\Clusters\Catalog\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;
}
