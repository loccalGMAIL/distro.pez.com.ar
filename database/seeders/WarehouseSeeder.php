<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $hasDefault = Warehouse::where('predeterminado', true)->exists();

        Warehouse::firstOrCreate(
            ['nombre' => 'Depósito Central'],
            ['tipo' => 'deposito', 'predeterminado' => ! $hasDefault, 'activo' => true],
        );

        Warehouse::firstOrCreate(
            ['nombre' => 'Camión Reparto'],
            ['tipo' => 'camion', 'predeterminado' => false, 'activo' => true],
        );
    }
}
