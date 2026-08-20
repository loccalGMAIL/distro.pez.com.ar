<?php

namespace App\Filament\Clusters\Finance\Resources\Expenses\Pages;

use App\Filament\Clusters\Finance\Resources\Expenses\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;
}
