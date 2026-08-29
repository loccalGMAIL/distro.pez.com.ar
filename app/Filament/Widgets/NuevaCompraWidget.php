<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class NuevaCompraWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.nueva-compra-widget';
}
