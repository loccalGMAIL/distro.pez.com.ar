<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 1000, 100000);

        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'tipo_comprobante' => fake()->randomElement(['factura_a', 'factura_b', 'factura_c', 'remito', 'otro']),
            'numero' => fake()->unique()->numerify('#####-########'),
            'fecha' => fake()->dateTimeBetween('-1 month'),
            'subtotal' => $total,
            'total' => $total,
            'saldo' => 0,
            'status' => 'borrador',
        ];
    }
}
