<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.razon_social')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.nombre')
                    ->label('Depósito')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('tipo_comprobante')
                    ->badge(),
                TextColumn::make('numero')
                    ->searchable(),
                TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('vence_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('saldo')
                    ->money('ARS')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
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
                SelectFilter::make('status')
                    ->options(['borrador' => 'Borrador', 'confirmada' => 'Confirmada', 'anulada' => 'Anulada']),
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
