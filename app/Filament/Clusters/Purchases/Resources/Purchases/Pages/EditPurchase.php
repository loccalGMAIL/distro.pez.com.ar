<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Pages;

use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use App\Models\Purchase;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $purchase = $this->getRecord();

        if ($purchase instanceof Purchase && $purchase->status !== 'borrador') {
            Notification::make()
                ->title('Solo se pueden editar compras en borrador.')
                ->warning()
                ->send();

            $this->redirect(PurchaseResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
