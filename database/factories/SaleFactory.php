<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 500, 50000);

        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'numero' => fake()->unique()->numerify('#####-########'),
            'fecha' => fake()->dateTimeBetween('-1 month'),
            'subtotal' => $total,
            'total' => $total,
            'saldo' => 0,
            'condicion_pago' => 'contado',
            'status' => 'borrador',
        ];
    }
}
