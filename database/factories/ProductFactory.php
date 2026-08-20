<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => fake()->unique()->ean13(),
            'nombre' => fake()->words(3, true),
            'product_category_id' => ProductCategory::factory(),
            'base_unit' => 'unidad',
            'costo_ultimo' => fake()->randomFloat(4, 50, 5000),
            'min_stock' => fake()->randomFloat(3, 0, 50),
            'tracks_lot' => false,
            'activo' => true,
        ];
    }
}
