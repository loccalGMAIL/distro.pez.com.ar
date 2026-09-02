<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentAllocator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imputar')
                ->label('Imputar automáticamente')
                ->icon(Heroicon::OutlinedBanknotes)
                ->visible(fn (Payment $record): bool => (float) $record->sin_imputar > 0)
                ->action(function (Payment $record) {
                    app(PaymentAllocator::class)->allocate($record);

                    Notification::make()
                        ->title('Pago imputado')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Cubre el caso de subir el monto de un pago ya guardado: reparte el
     * nuevo remanente entre las compras que sigan con saldo.
     */
    protected function afterSave(): void
    {
        /** @var Payment $record */
        $record = $this->record;

        app(PaymentAllocator::class)->allocate($record);
    }
}
