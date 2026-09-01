<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntries\Tables;

use App\Models\TimeEntry;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label('Fin')
                    ->dateTime()
                    ->placeholder('En curso')
                    ->sortable(),
                TextColumn::make('hours')
                    ->label('Horas')
                    ->state(fn (TimeEntry $record): float => $record->hours()),
                TextColumn::make('pay')
                    ->label('A cobrar')
                    ->state(fn (TimeEntry $record): string => '$'.number_format($record->pay(), 2, ',', '.')),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::fichajeOptions()),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde): Builder => $query->whereDate('started_at', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta): Builder => $query->whereDate('started_at', '<=', $hasta))),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
