<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\Tables;

use App\Models\TimeEntrySettlement;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TimeEntrySettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_pago', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Número')
                    ->state(fn (TimeEntrySettlement $record): string => $record->numero())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periodo_desde')
                    ->label('Período')
                    ->state(fn (TimeEntrySettlement $record): string => $record->periodoLegible())
                    ->sortable(),
                TextColumn::make('horas')
                    ->label('Horas')
                    ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                    ->sortable(),
                TextColumn::make('tarifa_hora')
                    ->label('Tarifa')
                    ->money('ARS')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('fecha_pago')
                    ->label('Pago')
                    ->date()
                    ->sortable(),
                TextColumn::make('medio_pago')
                    ->label('Medio')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'confirmada' ? 'success' : 'danger'),
                TextColumn::make('liquidatedBy.name')
                    ->label('Liquidó')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::fichajeOptions()),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'confirmada' => 'Confirmada',
                        'anulada' => 'Anulada',
                    ]),
            ])
            ->recordActions([
                Action::make('recibo')
                    ->label('Recibo')
                    ->iconButton()
                    ->icon(Heroicon::DocumentArrowDown)
                    ->color('gray')
                    ->url(fn (TimeEntrySettlement $record): string => route('time-entry-settlements.receipt.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('anular')
                    ->label('Anular')
                    ->iconButton()
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (TimeEntrySettlement $record): bool => $record->status !== 'anulada')
                    ->requiresConfirmation()
                    ->modalDescription('Anula la liquidación: los fichajes vuelven a contar como pendientes de cobro y se da de baja el gasto generado en Finanzas.')
                    ->action(fn (TimeEntrySettlement $record) => $record->anular()),
            ]);
    }
}
