<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Pages;

use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('escanear')
                ->label('Escanear factura')
                ->icon(Heroicon::OutlinedCamera)
                ->color('gray')
                ->url(ScanPurchase::getUrl()),
            CreateAction::make()
                ->modalWidth(Width::SixExtraLarge)
                ->createAnother(false),
        ];
    }
}
