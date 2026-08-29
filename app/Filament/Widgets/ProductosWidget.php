<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class ProductosWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.productos-widget';
}
