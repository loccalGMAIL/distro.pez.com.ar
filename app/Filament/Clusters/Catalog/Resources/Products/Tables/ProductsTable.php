<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\Tables;

use App\Models\PriceList;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withSum('stockMovements as stock_total', 'quantity')
                ->with(['stockMovements' => fn (Relation $relationQuery): Relation => $relationQuery
                    ->selectRaw('product_id, warehouse_id, SUM(quantity) as total')
                    ->groupBy('product_id', 'warehouse_id')
                    ->with('warehouse:id,nombre')]))
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
                TextColumn::make('stock_total')
                    ->label('Stock')
                    ->state(fn (Product $record): float => (float) $record->stock_total)
                    ->numeric()
                    ->sortable()
                    ->color(fn (Product $record): ?string => (float) $record->stock_total <= (float) $record->min_stock ? 'danger' : null)
                    ->tooltip(fn (Product $record): ?string => self::stockBreakdownTooltip($record)),
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
                Action::make('ajustarStock')
                    ->label('Ajustar stock')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Depósito')
                            ->options(fn (): Collection => Warehouse::where('activo', true)->pluck('nombre', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->helperText('Positivo = entrada, negativo = salida.')
                            ->required()
                            ->numeric(),
                        Textarea::make('motivo')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $record->stockMovements()->create([
                            'warehouse_id' => $data['warehouse_id'],
                            'quantity' => $data['quantity'],
                            'unit_cost' => $record->costo_ultimo,
                            'type' => 'ajuste',
                            'user_id' => auth()->id(),
                            'motivo' => $data['motivo'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Stock ajustado')
                            ->success()
                            ->send();
                    }),
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
     * Desglose de stock por depósito para el tooltip de la columna "Stock".
     * Usa la colección de stockMovements pre-agregada por producto+depósito
     * en modifyQueryUsing(), sin volver a golpear la base por fila.
     */
    private static function stockBreakdownTooltip(Product $record): ?string
    {
        $lines = $record->stockMovements
            ->map(fn (StockMovement $movement): string => sprintf(
                '%s: %s',
                $movement->warehouse->nombre ?? '—',
                rtrim(rtrim(number_format((float) $movement->total, 3, ',', '.'), '0'), ',')
            ));

        return $lines->isEmpty() ? null : $lines->implode("\n");
    }

    /**
     * Una columna por lista de precios activa, con el precio ya calculado.
     * El multiplicador de cada lista se calcula una sola vez acá (no depende
     * del producto), evitando recorrer la cadena de "basada en" por fila.
     *
     * @return array<int, TextColumn>
     */
    private static function priceListColumns(): array
    {
        return PriceList::orderedForDisplay()
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
}
