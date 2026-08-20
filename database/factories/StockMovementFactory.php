<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->randomFloat(3, -50, 50),
            'unit_cost' => fake()->randomFloat(4, 100, 5000),
            'type' => fake()->randomElement(['compra', 'venta', 'ajuste', 'devolucion', 'devolucion_prov', 'merma', 'transferencia']),
        ];
    }
}
