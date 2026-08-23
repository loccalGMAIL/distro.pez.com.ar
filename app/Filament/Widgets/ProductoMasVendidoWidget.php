<?php

namespace App\Filament\Widgets;

use App\Models\SaleLine;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProductoMasVendidoWidget extends Widget
{
    protected static ?int $sort = 6;

    protected string $view = 'filament.widgets.producto-mas-vendido-widget';

    protected int|string|array $columnSpan = [
        'default' => 2,
        'sm' => 2,
        'lg' => 2,
    ];

    protected function getViewData(): array
    {
        $topLine = SaleLine::query()
            ->select('product_id', DB::raw('SUM(cantidad) as total_cantidad'))
            ->whereHas('sale', function ($query) {
                $query->where('status', 'confirmada')
                    ->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_cantidad')
            ->with('product')
            ->first();

        return [
            'nombre' => $topLine?->product?->nombre ?? 'Sin ventas este mes',
        ];
    }
}
