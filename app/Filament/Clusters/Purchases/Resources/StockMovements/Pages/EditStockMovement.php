<?php

namespace App\Filament\Clusters\Purchases\Resources\StockMovements\Pages;

use App\Filament\Clusters\Purchases\Resources\StockMovements\StockMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockMovement extends EditRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
