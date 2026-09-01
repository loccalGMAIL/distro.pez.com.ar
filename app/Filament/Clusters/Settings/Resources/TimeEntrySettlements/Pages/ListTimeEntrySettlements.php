<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\Pages;

use App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\TimeEntrySettlementResource;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntrySettlements extends ListRecords
{
    protected static string $resource = TimeEntrySettlementResource::class;
}
