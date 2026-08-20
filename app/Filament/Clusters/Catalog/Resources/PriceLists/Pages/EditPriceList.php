<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists\Pages;

use App\Filament\Clusters\Catalog\Resources\PriceLists\PriceListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPriceList extends EditRecord
{
    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
