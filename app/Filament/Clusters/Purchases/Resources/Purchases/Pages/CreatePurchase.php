<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Pages;

use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;
}
