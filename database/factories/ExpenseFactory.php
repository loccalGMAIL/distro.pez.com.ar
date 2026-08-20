<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month'),
            'descripcion' => fake()->sentence(),
            'monto' => fake()->randomFloat(2, 100, 50000),
            'medio_pago' => fake()->randomElement(['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro']),
        ];
    }
}
