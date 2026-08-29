<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists\Pages;

use App\Filament\Clusters\Catalog\Resources\PriceLists\PriceListResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPriceLists extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
