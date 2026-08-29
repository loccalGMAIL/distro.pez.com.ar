<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
