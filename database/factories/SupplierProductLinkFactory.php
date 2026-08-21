<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProductLink>
 */
class SupplierProductLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'description_key' => fake()->unique()->words(3, true),
            'product_id' => Product::factory(),
        ];
    }
}
