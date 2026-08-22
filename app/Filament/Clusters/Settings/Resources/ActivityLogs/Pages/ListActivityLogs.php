<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityLogs\Pages;

use App\Filament\Clusters\Settings\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
