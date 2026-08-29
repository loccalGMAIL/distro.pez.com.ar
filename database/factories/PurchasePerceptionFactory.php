<?php

namespace Database\Factories;

use App\Models\PerceptionType;
use App\Models\Purchase;
use App\Models\PurchasePerception;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePerception>
 */
class PurchasePerceptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'perception_type_id' => PerceptionType::factory(),
            'descripcion' => fake()->words(3, true),
            'monto' => fake()->randomFloat(2, 10, 5000),
        ];
    }
}
