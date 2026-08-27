<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\Tables;

use App\Models\PriceList;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('category.nombre')
                    ->label('Categoría')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('base_unit')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('costo_ultimo')
                    ->label('Costo')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable()
                    ->toggleable(),
                ...self::priceListColumns(),
                TextColumn::make('min_stock')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('tracks_lot')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->boolean()
                    ->toggleable(),
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

    /**
     * Una columna por lista de precios activa, con el precio ya calculado.
     * El multiplicador de cada lista se calcula una sola vez acá (no depende
     * del producto), evitando recorrer la cadena de "basada en" por fila.
     *
     * Se muestran en el orden de negocio minorista, mayorista y VIP, en vez
     * del orden alfabético.
     *
     * @return array<int, TextColumn>
     */
    private static function priceListColumns(): array
    {
        return PriceList::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->sortBy(fn (PriceList $priceList): int => self::priceListSortOrder($priceList->nombre))
            ->map(function (PriceList $priceList): TextColumn {
                $multiplicador = $priceList->multiplicador();

                return TextColumn::make("price_list_{$priceList->id}")
                    ->label($priceList->nombre)
                    ->state(fn (Product $record): string => number_format((float) $record->costo_ultimo * $multiplicador, 2, '.', ''))
                    ->money('ARS', locale: 'es_AR')
                    ->toggleable();
            })
            ->all();
    }

    private static function priceListSortOrder(string $nombre): int
    {
        return match (true) {
            str_contains(mb_strtolower($nombre), 'minorista') => 0,
            str_contains(mb_strtolower($nombre), 'mayorista') => 1,
            str_contains(mb_strtolower($nombre), 'vip') => 2,
            default => 3,
        };
    }
}
