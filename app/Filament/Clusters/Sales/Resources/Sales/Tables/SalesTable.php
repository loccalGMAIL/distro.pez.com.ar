<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Tables;

use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->size(TextSize::ExtraSmall)
                    ->searchable(),
                TextColumn::make('fecha')
                    ->size(TextSize::ExtraSmall)
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('customer.razon_social')
                    ->label('Cliente')
                    ->size(TextSize::ExtraSmall)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('warehouse.nombre')
                    ->label('Depósito')
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(),
                TextColumn::make('priceList.nombre')
                    ->label('Lista de precios')
                    ->placeholder('Costo')
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->size(TextSize::ExtraSmall)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->money('ARS', locale: 'es_AR')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('saldo')
                    ->money('ARS', locale: 'es_AR')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('condicion_pago')
                    ->badge()
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(),
                TextColumn::make('medio_pago')
                    ->badge()
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->size(TextSize::ExtraSmall)
                    ->color(fn (string $state): string => match ($state) {
                        'confirmada' => 'success',
                        'anulada' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('enviado_at')
                    ->dateTime('d/m/Y H:i')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime('d/m/Y H:i')
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['borrador' => 'Borrador', 'confirmada' => 'Confirmada', 'anulada' => 'Anulada']),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->modalWidth(Width::SixExtraLarge),
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->iconButton()
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (Sale $record): bool => $record->status === 'borrador')
                    ->requiresConfirmation()
                    ->modalDescription('Confirma la venta y descuenta el stock de cada línea.')
                    ->action(fn (Sale $record) => $record->confirmar()),
                Action::make('anular')
                    ->label('Anular')
                    ->iconButton()
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (Sale $record): bool => $record->status !== 'anulada')
                    ->requiresConfirmation()
                    ->modalDescription('Anula la venta, devuelve el stock descontado y revierte cualquier cobro imputado. No se puede deshacer.')
                    ->action(fn (Sale $record) => $record->anular()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
