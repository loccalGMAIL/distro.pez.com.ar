<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Tables;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\PaymentAllocator;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                Action::make('imputar')
                    ->label('Imputar')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->visible(fn (Payment $record): bool => (float) $record->sin_imputar > 0)
                    ->action(function (Payment $record) {
                        app(PaymentAllocator::class)->allocate($record);

                        Notification::make()->title('Pago imputado')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('imputar')
                        ->label('Imputar automáticamente')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->action(function (Collection $records) {
                            $allocator = app(PaymentAllocator::class);

                            foreach ($records as $record) {
                                if ($record instanceof Payment) {
                                    $allocator->allocate($record);
                                }
                            }

                            Notification::make()->title('Pagos imputados')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
