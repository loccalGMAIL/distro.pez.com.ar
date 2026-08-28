<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntries\Pages;

use App\Filament\Clusters\Settings\Resources\TimeEntries\TimeEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;
}
