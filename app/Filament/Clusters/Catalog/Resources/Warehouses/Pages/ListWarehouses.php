<?php

namespace App\Filament\Clusters\Catalog\Resources\Warehouses\Pages;

use App\Filament\Clusters\Catalog\Resources\Warehouses\WarehouseResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
