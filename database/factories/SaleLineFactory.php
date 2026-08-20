<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleLine>
 */
class SaleLineFactory extends Factory
{
    public function definition(): array
    {
        $cantidad = fake()->randomFloat(3, 1, 20);
        $precioUnit = fake()->randomFloat(2, 100, 5000);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'cantidad' => $cantidad,
            'precio_unit' => $precioUnit,
            'subtotal' => $cantidad * $precioUnit,
        ];
    }
}
