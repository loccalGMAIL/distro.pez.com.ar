<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('predeterminado', true)->first() ?? Warehouse::first();
        $user = User::first();
        $productos = Product::all();

        // [cliente, cantidad de líneas, qué hacer después de armarla]
        $plan = [
            ['cliente' => 'Consumidor Final', 'lineas' => 2, 'accion' => 'confirmar'],
            ['cliente' => 'Almacén Don José', 'lineas' => 3, 'accion' => 'confirmar'],
            ['cliente' => 'Kiosco La Esquina', 'lineas' => 1, 'accion' => 'confirmar'],
            ['cliente' => 'Supermercado Rivadavia', 'lineas' => 2, 'accion' => 'confirmar'],
            ['cliente' => 'Panadería San Martín', 'lineas' => 2, 'accion' => 'confirmar'],
            ['cliente' => 'Despensa Los Aromos', 'lineas' => 1, 'accion' => 'anular'],
            ['cliente' => 'Minimercado 24hs', 'lineas' => 3, 'accion' => 'confirmar'],
            ['cliente' => 'Verdulería Frutimax', 'lineas' => 1, 'accion' => null],
        ];

        foreach ($plan as $index => $item) {
            $customer = Customer::where('razon_social', $item['cliente'])->first();

            if (! $customer) {
                continue;
            }

            $sale = Sale::create([
                'customer_id' => $customer->id,
                'price_list_id' => $customer->price_list_id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user?->id,
                'fecha' => now()->subDays(random_int(0, 14)),
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
                'condicion_pago' => $customer->condicion_pago,
                'medio_pago' => $customer->condicion_pago === 'contado' ? 'efectivo' : null,
                'status' => 'borrador',
            ]);

            $lineas = $productos->random(min($item['lineas'], $productos->count()));
            $total = 0;

            foreach ($lineas as $producto) {
                $cantidad = random_int(1, 5);
                $precioUnit = (float) $producto->precioParaLista($sale->price_list_id);
                $subtotal = $cantidad * $precioUnit;
                $total += $subtotal;

                SaleLine::create([
                    'sale_id' => $sale->id,
                    'product_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unit' => $precioUnit,
                    'descuento' => 0,
                    'subtotal' => $subtotal,
                    'costo_unit' => $producto->costo_ultimo,
                ]);
            }

            $sale->update(['subtotal' => $total, 'total' => $total]);

            match ($item['accion']) {
                'confirmar' => $sale->confirmar(),
                'anular' => tap($sale)->confirmar()->anular(),
                default => null,
            };
        }
    }
}
