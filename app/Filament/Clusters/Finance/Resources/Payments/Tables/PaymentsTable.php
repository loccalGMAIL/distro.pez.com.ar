<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Tables;

use App\Models\Customer;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('party_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Supplier::class => 'Proveedor',
                        Customer::class => 'Cliente',
                        default => (string) $state,
                    }),
                TextColumn::make('party.razon_social')
                    ->label('Proveedor / Cliente')
                    ->searchable(),
                TextColumn::make('direccion')
                    ->badge(),
                TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('monto')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('medio_pago')
                    ->badge(),
                TextColumn::make('referencia')
                    ->searchable(),
                TextColumn::make('sin_imputar')
                    ->label('Sin imputar')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
