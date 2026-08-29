<?php

namespace App\Filament\Clusters\Finance\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.nombre')
                    ->label('Categoría')
                    ->sortable(),
                TextColumn::make('supplier.razon_social')
                    ->label('Proveedor')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Usuario'),
                TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('monto')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('medio_pago')
                    ->badge(),
                TextColumn::make('comprobante_numero')
                    ->searchable(),
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
