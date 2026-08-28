<?php

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        $hasDefault = PriceList::where('predeterminada', true)->exists();

        $mayorista = PriceList::firstOrCreate(
            ['codigo' => 'LP-001'],
            ['nombre' => 'Mayorista', 'porcentaje' => 25, 'predeterminada' => false, 'compartible' => true, 'activo' => true],
        );

        // Minorista = Mayorista + 20% (encadenada). Predeterminada solo si
        // todavía no hay ninguna lista marcada como tal.
        PriceList::firstOrCreate(
            ['codigo' => 'LP-002'],
            [
                'nombre' => 'Minorista',
                'based_on_price_list_id' => $mayorista->id,
                'porcentaje' => 20,
                'predeterminada' => ! $hasDefault,
                'compartible' => true,
                'activo' => true,
            ],
        );

        // Lista VIP = Mayorista - 5% (encadenada), para clientes grandes.
        PriceList::firstOrCreate(
            ['codigo' => 'LP-003'],
            [
                'nombre' => 'Lista VIP',
                'based_on_price_list_id' => $mayorista->id,
                'porcentaje' => -5,
                'predeterminada' => false,
                'compartible' => true,
                'activo' => true,
            ],
        );
    }
}
