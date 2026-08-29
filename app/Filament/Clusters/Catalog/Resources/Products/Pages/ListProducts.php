<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\Pages;

use App\Filament\Clusters\Catalog\Resources\Products\ProductResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
