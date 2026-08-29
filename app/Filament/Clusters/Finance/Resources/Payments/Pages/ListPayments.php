<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
