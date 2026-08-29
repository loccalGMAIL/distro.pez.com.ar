<?php

namespace App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages;

use App\Filament\Clusters\Settings\Resources\PerceptionTypes\PerceptionTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPerceptionType extends EditRecord
{
    protected static string $resource = PerceptionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
