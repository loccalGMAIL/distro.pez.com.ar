<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            // Almacén
            ['nombre' => 'Arroz Largo Fino 1kg', 'categoria' => 'Almacén', 'unidad' => 'kg', 'costo' => 850],
            ['nombre' => 'Fideos Tallarín 500g', 'categoria' => 'Almacén', 'unidad' => 'unidad', 'costo' => 620],
            ['nombre' => 'Aceite de Girasol 1.5L', 'categoria' => 'Almacén', 'unidad' => 'litro', 'costo' => 1800],
            ['nombre' => 'Azúcar 1kg', 'categoria' => 'Almacén', 'unidad' => 'kg', 'costo' => 750],
            ['nombre' => 'Yerba Mate 1kg', 'categoria' => 'Almacén', 'unidad' => 'kg', 'costo' => 2400],
            ['nombre' => 'Harina 000 1kg', 'categoria' => 'Almacén', 'unidad' => 'kg', 'costo' => 680],
            // Bebidas
            ['nombre' => 'Gaseosa Cola 2.25L', 'categoria' => 'Bebidas', 'unidad' => 'litro', 'costo' => 1450],
            ['nombre' => 'Agua Mineral 1.5L', 'categoria' => 'Bebidas', 'unidad' => 'litro', 'costo' => 650],
            ['nombre' => 'Cerveza Rubia 1L', 'categoria' => 'Bebidas', 'unidad' => 'litro', 'costo' => 1200],
            ['nombre' => 'Jugo de Naranja 1L', 'categoria' => 'Bebidas', 'unidad' => 'litro', 'costo' => 980],
            // Lácteos
            ['nombre' => 'Leche Entera 1L', 'categoria' => 'Lácteos', 'unidad' => 'litro', 'costo' => 900],
            ['nombre' => 'Queso Cremoso 1kg', 'categoria' => 'Lácteos', 'unidad' => 'kg', 'costo' => 5200],
            ['nombre' => 'Yogur Bebible 1L', 'categoria' => 'Lácteos', 'unidad' => 'litro', 'costo' => 1350],
            // Limpieza
            ['nombre' => 'Detergente 750ml', 'categoria' => 'Limpieza', 'unidad' => 'unidad', 'costo' => 1100],
            ['nombre' => 'Lavandina 1L', 'categoria' => 'Limpieza', 'unidad' => 'litro', 'costo' => 620],
            ['nombre' => 'Jabón en Polvo 800g', 'categoria' => 'Limpieza', 'unidad' => 'unidad', 'costo' => 1950],
            // Snacks
            ['nombre' => 'Papas Fritas 150g', 'categoria' => 'Snacks', 'unidad' => 'unidad', 'costo' => 1300],
            ['nombre' => 'Galletitas Dulces 300g', 'categoria' => 'Snacks', 'unidad' => 'unidad', 'costo' => 890],
            ['nombre' => 'Chocolate 100g', 'categoria' => 'Snacks', 'unidad' => 'unidad', 'costo' => 1450],
        ];

        $categorias = ProductCategory::pluck('id', 'nombre');

        foreach ($productos as $index => $producto) {
            $numero = $index + 1;

            Product::firstOrCreate(
                ['sku' => 'SKU-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT)],
                [
                    'barcode' => '779'.str_pad((string) $numero, 10, '0', STR_PAD_LEFT),
                    'nombre' => $producto['nombre'],
                    'product_category_id' => $categorias[$producto['categoria']],
                    'base_unit' => $producto['unidad'],
                    'costo_ultimo' => $producto['costo'],
                    'min_stock' => 10,
                    'tracks_lot' => false,
                    'activo' => true,
                ],
            );
        }
    }
}
