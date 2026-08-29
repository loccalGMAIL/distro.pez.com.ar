<?php

namespace App\Filament\Clusters\Catalog\Resources\StockMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('warehouse.nombre')
                    ->label('Depósito'),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Costo unitario')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('user.name')
                    ->label('Usuario'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'ajuste' => 'Ajuste',
                        'devolucion' => 'Devolución',
                        'devolucion_prov' => 'Devolución a proveedor',
                        'merma' => 'Merma',
                        'transferencia' => 'Transferencia',
                    ]),
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
