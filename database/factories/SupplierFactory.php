<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('PROV-####'),
            'razon_social' => fake()->company(),
            'cuit' => fake()->numerify('30-########-#'),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'domicilio' => fake()->streetAddress(),
            'condicion_pago' => fake()->randomElement(['contado', 'cuenta_corriente']),
            'dias_pago' => fake()->randomElement([0, 15, 30, 60]),
            'balance' => 0,
            'activo' => true,
        ];
    }
}
