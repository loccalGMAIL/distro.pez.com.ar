<?php

use App\Services\PurchaseCostAllocator;

test('distribuir prorratea impuestos que afectan costo proporcional al neto de cada línea', function () {
    // Harina: neto $10, IVA 10,5% + IIBB 4% + IVA percepción 1,5% = $1,60 => costo final $11,60.
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [['cantidad' => 1.0, 'subtotal' => 10.0]],
        impuestosQueAfectanCosto: 1.60,
        descuento: 0.0,
    );

    expect($costos[0])->toBe(11.6);
});

test('distribuir reparte proporcional al subtotal de cada línea, no en partes iguales', function () {
    // Neto $1.000 (harina $600 + azúcar $400), impuesto $40 => 60%/40% => $24 y $16.
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [
            ['cantidad' => 1.0, 'subtotal' => 600.0],
            ['cantidad' => 1.0, 'subtotal' => 400.0],
        ],
        impuestosQueAfectanCosto: 40.0,
        descuento: 0.0,
    );

    expect($costos[0])->toBe(624.0);
    expect($costos[1])->toBe(416.0);
});

test('distribuir resta el descuento de cabecera antes de repartir', function () {
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [['cantidad' => 1.0, 'subtotal' => 100.0]],
        impuestosQueAfectanCosto: 21.0,
        descuento: 10.0,
    );

    // 100 + (21 - 10) = 111
    expect($costos[0])->toBe(111.0);
});

test('distribuir divide el ajuste por cantidad para obtener el costo unitario', function () {
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [['cantidad' => 4.0, 'subtotal' => 400.0]],
        impuestosQueAfectanCosto: 40.0,
        descuento: 0.0,
    );

    // Neto $400 + impuesto $40 = $440 / 4 unidades = $110 c/u.
    expect($costos[0])->toBe(110.0);
});

test('distribuir ajusta el residuo de redondeo en la línea de mayor subtotal para que la suma cierre exacto', function () {
    $lineas = [
        ['cantidad' => 1.0, 'subtotal' => 33.33],
        ['cantidad' => 1.0, 'subtotal' => 33.33],
        ['cantidad' => 1.0, 'subtotal' => 33.34],
    ];

    $costos = (new PurchaseCostAllocator)->distribuir($lineas, impuestosQueAfectanCosto: 10.0, descuento: 0.0);

    $sumaFinal = array_sum(array_map(
        fn (float $costo, array $linea): float => $costo * $linea['cantidad'],
        $costos,
        $lineas
    ));

    expect(round($sumaFinal, 2))->toBe(round(33.33 + 33.33 + 33.34 + 10.0, 2));
});

test('distribuir no reparte nada si la suma de subtotales es cero, evitando división por cero', function () {
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [['cantidad' => 1.0, 'subtotal' => 0.0]],
        impuestosQueAfectanCosto: 50.0,
        descuento: 0.0,
    );

    expect($costos[0])->toBe(0.0);
});

test('distribuir devuelve costo final cero para una línea con cantidad cero, sin dividir por cero', function () {
    $costos = (new PurchaseCostAllocator)->distribuir(
        lineas: [
            ['cantidad' => 0.0, 'subtotal' => 0.0],
            ['cantidad' => 1.0, 'subtotal' => 100.0],
        ],
        impuestosQueAfectanCosto: 21.0,
        descuento: 0.0,
    );

    expect($costos[0])->toBe(0.0);
    expect($costos[1])->toBe(121.0);
});

test('distribuir con lista vacía de líneas devuelve lista vacía', function () {
    $costos = (new PurchaseCostAllocator)->distribuir(lineas: [], impuestosQueAfectanCosto: 100.0, descuento: 0.0);

    expect($costos)->toBe([]);
});
