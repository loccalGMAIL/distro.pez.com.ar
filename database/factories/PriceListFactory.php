<?php

namespace Database\Factories;

use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceList>
 */
class PriceListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'codigo' => fake()->unique()->bothify('LP-##'),
            'porcentaje' => fake()->randomFloat(2, 0, 100),
            'compartible' => true,
            'activo' => true,
        ];
    }
}
