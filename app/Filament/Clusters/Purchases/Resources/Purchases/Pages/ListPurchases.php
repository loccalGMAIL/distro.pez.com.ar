<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Pages;

use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::SixExtraLarge)
                ->createAnother(false),
        ];
    }
}
