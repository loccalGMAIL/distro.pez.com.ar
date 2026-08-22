<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema'),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Str::afterLast($state, '\\') : null)
                    ->badge(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextEntry::make('causer.name')
                            ->label('Usuario')
                            ->default('Sistema'),
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('subject_type')
                            ->label('Modelo')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Str::afterLast($state, '\\') : null),
                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime(),
                        TextEntry::make('properties')
                            ->label('Cambios')
                            ->formatStateUsing(function (array|Collection|null $state): string {
                                $properties = $state instanceof Collection ? $state->all() : ($state ?? []);

                                return filled($properties)
                                    ? json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                    : '—';
                            })
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
