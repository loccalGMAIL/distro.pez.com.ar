<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class ProductosActivosWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 7;

    protected string $view = 'filament.widgets.productos-activos-widget';

    protected int|string|array $columnSpan = [
        'default' => 2,
        'sm' => 1,
        'lg' => 1,
    ];

    protected function getViewData(): array
    {
        return [
            'total' => Product::where('activo', true)->count(),
        ];
    }
}
