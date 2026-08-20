<?php

namespace App\Filament\Clusters\Purchases\Resources\StockMovements\Pages;

use App\Filament\Clusters\Purchases\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;
}
