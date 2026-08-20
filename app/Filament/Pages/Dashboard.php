<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva_venta')
                ->label('Nueva venta')
                ->icon(Heroicon::Plus)
                ->url(SaleResource::getUrl(parameters: ['action' => 'create'])),
        ];
    }
}
