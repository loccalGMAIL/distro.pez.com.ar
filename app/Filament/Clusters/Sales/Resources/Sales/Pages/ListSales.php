<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::SixExtraLarge)
                ->createAnother(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->before(function (CreateAction $action, array $data) {
                    if ((float) ($data['total'] ?? 0) <= 0.0) {
                        Notification::make()
                            ->title('El total de la venta debe ser mayor a $0,00.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                })
                ->after(function (Sale $record) {
                    if ($record->status === 'confirmada') {
                        $record->deducirStock();
                        $record->comprobanteNotification()->send();
                    }
                }),
        ];
    }
}
