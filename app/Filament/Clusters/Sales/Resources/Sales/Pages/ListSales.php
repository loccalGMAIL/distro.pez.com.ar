<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::SixExtraLarge)
                ->createAnother(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->after(function (Sale $record) {
                    if ($record->status === 'confirmada') {
                        $record->deducirStock();
                    }
                }),
        ];
    }
}
