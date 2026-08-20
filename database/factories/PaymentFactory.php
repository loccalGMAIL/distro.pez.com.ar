<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'party_type' => Supplier::class,
            'party_id' => Supplier::factory(),
            'direccion' => 'egreso',
            'fecha' => fake()->dateTimeBetween('-1 month'),
            'monto' => fake()->randomFloat(2, 500, 50000),
            'medio_pago' => fake()->randomElement(['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro']),
            'sin_imputar' => 0,
        ];
    }
}
