<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class VentasDelMesWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 5;

    protected string $view = 'filament.widgets.ventas-del-mes-widget';

    protected int|string|array $columnSpan = [
        'default' => 2,
        'sm' => 1,
        'lg' => 1,
    ];

    protected function getViewData(): array
    {
        $total = Sale::query()
            ->where('status', 'confirmada')
            ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        return [
            'total' => '$'.number_format((float) $total, 2, ',', '.'),
        ];
    }
}
