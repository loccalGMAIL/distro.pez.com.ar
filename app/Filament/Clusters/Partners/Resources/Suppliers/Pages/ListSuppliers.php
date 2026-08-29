<?php

namespace App\Filament\Clusters\Partners\Resources\Suppliers\Pages;

use App\Filament\Clusters\Partners\Resources\Suppliers\SupplierResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
