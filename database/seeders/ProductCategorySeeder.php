<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Almacén', 'Bebidas', 'Lácteos', 'Limpieza', 'Snacks'] as $nombre) {
            ProductCategory::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
