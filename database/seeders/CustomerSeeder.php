<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PriceList;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $minorista = PriceList::where('codigo', 'LP-002')->value('id');
        $mayorista = PriceList::where('codigo', 'LP-001')->value('id');
        $vip = PriceList::where('codigo', 'LP-003')->value('id');

        $clientes = [
            ['razon_social' => 'Consumidor Final', 'lista' => $minorista, 'predeterminado' => true, 'condicion' => 'contado'],
            ['razon_social' => 'Almacén Don José', 'lista' => $mayorista, 'condicion' => 'cuenta_corriente'],
            ['razon_social' => 'Kiosco La Esquina', 'lista' => $minorista, 'condicion' => 'contado'],
            ['razon_social' => 'Supermercado Rivadavia', 'lista' => $vip, 'condicion' => 'cuenta_corriente'],
            ['razon_social' => 'Panadería San Martín', 'lista' => $mayorista, 'condicion' => 'cuenta_corriente'],
            ['razon_social' => 'Despensa Los Aromos', 'lista' => $minorista, 'condicion' => 'contado'],
            ['razon_social' => 'Minimercado 24hs', 'lista' => $mayorista, 'condicion' => 'cuenta_corriente'],
            ['razon_social' => 'Verdulería Frutimax', 'lista' => $minorista, 'condicion' => 'contado'],
            ['razon_social' => 'Rotisería El Buen Sabor', 'lista' => $minorista, 'condicion' => 'contado'],
            ['razon_social' => 'Comercial Belgrano', 'lista' => $vip, 'condicion' => 'cuenta_corriente'],
        ];

        foreach ($clientes as $index => $cliente) {
            $numero = $index + 1;

            Customer::firstOrCreate(
                ['codigo' => 'CLI-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT)],
                [
                    'razon_social' => $cliente['razon_social'],
                    'telefono' => '11-5000-'.str_pad((string) (1000 + $numero), 4, '0', STR_PAD_LEFT),
                    'localidad' => 'Buenos Aires',
                    'condicion_pago' => $cliente['condicion'],
                    'balance' => 0,
                    'price_list_id' => $cliente['lista'],
                    'predeterminado' => $cliente['predeterminado'] ?? false,
                    'activo' => true,
                ],
            );
        }
    }
}
