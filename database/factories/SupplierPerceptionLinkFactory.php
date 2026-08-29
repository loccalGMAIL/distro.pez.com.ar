<?php

namespace Database\Factories;

use App\Models\PerceptionType;
use App\Models\Supplier;
use App\Models\SupplierPerceptionLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierPerceptionLink>
 */
class SupplierPerceptionLinkFactory extends Factory
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
            'perception_type_id' => PerceptionType::factory(),
        ];
    }
}
