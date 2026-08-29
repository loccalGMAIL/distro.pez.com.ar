<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class NuevaVentaWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.nueva-venta-widget';
}
