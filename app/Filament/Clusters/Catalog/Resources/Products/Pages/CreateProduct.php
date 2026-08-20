<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\Pages;

use App\Filament\Clusters\Catalog\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
