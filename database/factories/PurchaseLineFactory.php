<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseLine>
 */
class PurchaseLineFactory extends Factory
{
    public function definition(): array
    {
        $cantidad = fake()->randomFloat(3, 1, 100);
        $costoUnit = fake()->randomFloat(4, 100, 5000);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'cantidad' => $cantidad,
            'costo_unit' => $costoUnit,
            'subtotal' => $cantidad * $costoUnit,
        ];
    }
}
