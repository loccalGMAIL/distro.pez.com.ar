<?php

namespace App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages;

use App\Filament\Clusters\Settings\Resources\PerceptionTypes\PerceptionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerceptionTypes extends ListRecords
{
    protected static string $resource = PerceptionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
