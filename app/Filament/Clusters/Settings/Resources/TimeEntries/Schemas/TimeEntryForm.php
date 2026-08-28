<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntries\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::administrativoOptions())
                    ->searchable()
                    ->required(),
                DateTimePicker::make('started_at')
                    ->label('Inicio')
                    ->required(),
                DateTimePicker::make('ended_at')
                    ->label('Fin')
                    ->after('started_at'),
            ]);
    }
}
