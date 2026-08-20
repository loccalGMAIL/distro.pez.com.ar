<?php

namespace App\Filament\Clusters\Partners\Resources\Customers\Pages;

use App\Filament\Clusters\Partners\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
