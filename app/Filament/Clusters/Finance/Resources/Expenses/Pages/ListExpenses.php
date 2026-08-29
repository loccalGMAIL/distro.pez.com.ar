<?php

namespace App\Filament\Clusters\Finance\Resources\Expenses\Pages;

use App\Filament\Clusters\Finance\Resources\Expenses\ExpenseResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
