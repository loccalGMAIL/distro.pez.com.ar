<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityLogs\Pages;

use App\Filament\Clusters\Settings\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Concerns\PersistsTableColumnsForUser;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    use PersistsTableColumnsForUser;

    protected static string $resource = ActivityLogResource::class;
}
