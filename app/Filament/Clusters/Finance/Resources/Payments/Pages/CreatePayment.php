<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Clusters\Finance\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
