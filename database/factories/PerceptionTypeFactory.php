<?php

namespace Database\Factories;

use App\Models\PerceptionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerceptionType>
 */
class PerceptionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'activo' => true,
        ];
    }
}
