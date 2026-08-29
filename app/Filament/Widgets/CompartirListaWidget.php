<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class CompartirListaWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.compartir-lista-widget';
}
