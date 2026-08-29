<?php

namespace App\Filament\Clusters\Settings\Resources\PerceptionTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PerceptionTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }
}
