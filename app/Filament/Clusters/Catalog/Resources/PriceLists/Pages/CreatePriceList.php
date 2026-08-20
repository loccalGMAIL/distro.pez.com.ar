<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists\Pages;

use App\Filament\Clusters\Catalog\Resources\PriceLists\PriceListResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceList extends CreateRecord
{
    protected static string $resource = PriceListResource::class;
}
