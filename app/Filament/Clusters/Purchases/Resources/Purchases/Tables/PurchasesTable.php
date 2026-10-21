<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Tables;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('supplier.razon_social')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tipo_comprobante')
                    ->label('Comprobante')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('warehouse.nombre')
                    ->label('Depósito')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('vence_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product_units')
                    ->label('Unidades')
                    ->state(fn (Purchase $record): float => (float) $record->product_units)
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn (HasTable $livewire): bool => filled($livewire->getTableFilterState('product_id')['value'] ?? null)),
                TextColumn::make('total')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('saldo')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmada' => 'success',
                        'anulada' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['borrador' => 'Borrador', 'confirmada' => 'Confirmada', 'anulada' => 'Anulada']),
                Filter::make('ocultar_anuladas')
                    ->label('Ocultar anuladas')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', 'anulada')),
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'razon_social')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('product_id')
                    ->label('Producto')
                    ->options(fn (): Collection => Product::query()
                        ->whereIn('id', PurchaseLine::query()->select('product_id'))
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => self::filterByProduct($query, $data['value'] ?? null)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton()
                    ->visible(fn (Purchase $record): bool => $record->status === 'borrador'),
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->iconButton()
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (Purchase $record): bool => $record->status === 'borrador')
                    ->requiresConfirmation()
                    ->modalDescription('Confirma la compra e ingresa el stock de cada línea, actualizando el costo vigente de los productos.')
                    ->action(fn (Purchase $record) => $record->confirmar()),
                Action::make('anular')
                    ->label('Anular')
                    ->iconButton()
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (Purchase $record): bool => $record->status !== 'anulada')
                    ->requiresConfirmation()
                    ->modalDescription('Anula la compra, revierte el stock ingresado y cualquier pago imputado. No se puede deshacer.')
                    ->action(fn (Purchase $record) => $record->anular()),
                Action::make('verArchivo')
                    ->label('Factura')
                    ->iconButton()
                    ->icon(Heroicon::DocumentArrowDown)
                    ->color('gray')
                    ->visible(fn (Purchase $record): bool => filled($record->archivo_path))
                    ->url(fn (Purchase $record): string => route('purchases.archivo', $record))
                    ->openUrlInNewTab(),
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
     * Deja solo las compras que incluyen el producto y suma sus unidades
     * (puede venir en más de una línea) en `product_units` para la columna "Unidades".
     *
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    private static function filterByProduct(Builder $query, mixed $productId): Builder
    {
        if (blank($productId)) {
            return $query;
        }

        return $query
            ->whereHas('lines', fn (Builder $lineQuery): Builder => $lineQuery->where('product_id', $productId))
            ->withSum(['lines as product_units' => fn (Builder $lineQuery): Builder => $lineQuery->where('product_id', $productId)], 'cantidad');
    }
}
