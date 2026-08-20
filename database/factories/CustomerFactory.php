<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('CLI-####'),
            'razon_social' => fake()->company(),
            'cuit' => fake()->numerify('20-########-#'),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'domicilio' => fake()->streetAddress(),
            'localidad' => fake()->city(),
            'condicion_pago' => fake()->randomElement(['contado', 'cuenta_corriente']),
            'price_list_id' => PriceList::factory(),
            'balance' => 0,
            'activo' => true,
        ];
    }
}
