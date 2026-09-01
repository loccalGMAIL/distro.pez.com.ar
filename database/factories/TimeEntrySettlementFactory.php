<?php

namespace Database\Factories;

use App\Models\TimeEntrySettlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntrySettlement>
 */
class TimeEntrySettlementFactory extends Factory
{
    public function definition(): array
    {
        $horas = fake()->randomFloat(2, 10, 160);
        $tarifa = fake()->randomFloat(2, 500, 5000);
        $desde = fake()->dateTimeBetween('-2 months', '-1 month');

        return [
            'user_id' => User::factory(),
            'periodo_desde' => $desde,
            'periodo_hasta' => (clone $desde)->modify('+1 month'),
            'horas' => $horas,
            'tarifa_hora' => $tarifa,
            'total' => round($horas * $tarifa, 2),
            'status' => 'confirmada',
            'fecha_pago' => fake()->dateTimeBetween('-1 month'),
            'medio_pago' => fake()->randomElement(['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro']),
        ];
    }

    public function anulada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'anulada',
        ]);
    }
}
