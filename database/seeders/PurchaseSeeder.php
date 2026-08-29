<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('predeterminado', true)->first() ?? Warehouse::first();
        $productos = Product::all();

        // Una compra confirmada por proveedor, con 2 a 4 líneas de productos
        // al azar, para tener historial en el módulo de Compras.
        Supplier::all()->each(function (Supplier $supplier) use ($warehouse, $productos) {
            $lineas = $productos->random(min(4, $productos->count()));

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'tipo_comprobante' => 'factura_a',
                'numero' => fake()->unique()->numerify('0001-########'),
                'fecha' => now()->subDays(random_int(1, 20)),
                'subtotal' => 0,
                'total' => 0,
                'saldo' => 0,
                'status' => 'confirmada',
            ]);

            $total = 0;

            foreach ($lineas as $producto) {
                $cantidad = random_int(5, 30);
                $costoUnit = (float) $producto->costo_ultimo;
                $subtotal = $cantidad * $costoUnit;
                $total += $subtotal;

                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'costo_unit' => $costoUnit,
                    'subtotal' => $subtotal,
                ]);
            }

            $purchase->update(['subtotal' => $total, 'total' => $total, 'saldo' => $total]);
            $supplier->recalcularBalance();
        });

        // Stock inicial: todo producto arranca con existencia en el depósito
        // principal, para que las ventas seed tengan de dónde descontar.
        foreach ($productos as $producto) {
            StockMovement::create([
                'product_id' => $producto->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 100,
                'unit_cost' => $producto->costo_ultimo,
                'type' => 'compra',
                'motivo' => 'Stock inicial',
            ]);
        }
    }
}
