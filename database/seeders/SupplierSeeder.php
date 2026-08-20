<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            ['razon_social' => 'Distribuidora del Norte S.A.', 'cuit' => '30-71234567-1', 'dias_pago' => 30],
            ['razon_social' => 'Molinos del Sur S.R.L.', 'cuit' => '30-71234568-2', 'dias_pago' => 15],
            ['razon_social' => 'Lácteos La Serrana', 'cuit' => '30-71234569-3', 'dias_pago' => 0],
            ['razon_social' => 'Bebidas Andinas S.A.', 'cuit' => '30-71234570-4', 'dias_pago' => 30],
            ['razon_social' => 'Limpieza Total Mayorista', 'cuit' => '30-71234571-5', 'dias_pago' => 15],
        ];

        foreach ($proveedores as $index => $proveedor) {
            $numero = $index + 1;

            Supplier::firstOrCreate(
                ['codigo' => 'PROV-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT)],
                [
                    'razon_social' => $proveedor['razon_social'],
                    'cuit' => $proveedor['cuit'],
                    'telefono' => '11-4000-'.str_pad((string) (1000 + $numero), 4, '0', STR_PAD_LEFT),
                    'condicion_pago' => $proveedor['dias_pago'] > 0 ? 'cuenta_corriente' : 'contado',
                    'dias_pago' => $proveedor['dias_pago'],
                    'balance' => 0,
                    'activo' => true,
                ],
            );
        }
    }
}
