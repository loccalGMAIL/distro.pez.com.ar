<?php

namespace Database\Factories;

use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'razon_social' => fake()->company(),
            'cuit' => fake()->numerify('##-########-#'),
            'condicion_iva' => fake()->randomElement([
                'responsable_inscripto',
                'monotributo',
                'exento',
                'consumidor_final',
                'no_responsable',
            ]),
            'domicilio_fiscal' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'punto_venta' => fake()->numerify('####'),
            'proximo_numero_comprobante' => 1,
        ];
    }
}
