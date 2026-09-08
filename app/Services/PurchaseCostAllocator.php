<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePerception;

class PurchaseCostAllocator
{
    /**
     * Reparte a cada línea, proporcional a su neto (subtotal), los impuestos
     * de la factura que afectan costo — descontado el descuento de cabecera
     * — y devuelve el costo unitario final resultante. Único lugar donde
     * vive esta cuenta: los formularios de compra la llaman para
     * previsualizar sobre el estado del form, y aplicar() la usa para
     * persistir sobre el modelo.
     *
     * Proporcional al neto y no "% de la percepción por línea" dan el mismo
     * resultado cuando la percepción se aplica a toda la factura (el caso
     * normal); solo divergen con alícuotas mezcladas en un mismo
     * comprobante, caso que hoy no se modela (ver .ai/rules).
     *
     * El residuo de redondeo (centavos que sobran/faltan al repartir en
     * decimales) se ajusta en la línea de mayor subtotal, para que
     * sum(costo_final × cantidad) cierre exacto contra
     * neto − descuento + impuestos.
     *
     * @param  array<int, array{cantidad: float, subtotal: float}>  $lineas
     * @return array<int, float> costo unitario final por línea, mismo orden y claves que $lineas
     */
    public function distribuir(array $lineas, float $impuestosQueAfectanCosto, float $descuento): array
    {
        $netoTotal = array_sum(array_map(fn (array $linea): float => (float) $linea['subtotal'], $lineas));

        $ajusteTotal = $impuestosQueAfectanCosto - $descuento;

        if ($netoTotal <= 0.0 || $lineas === []) {
            return array_map(
                fn (array $linea): float => (float) $linea['cantidad'] > 0
                    ? round((float) $linea['subtotal'] / (float) $linea['cantidad'], 4)
                    : 0.0,
                $lineas
            );
        }

        $keyMayorSubtotal = null;
        $mayorSubtotal = -INF;

        foreach ($lineas as $key => $linea) {
            if ((float) $linea['subtotal'] > $mayorSubtotal) {
                $mayorSubtotal = (float) $linea['subtotal'];
                $keyMayorSubtotal = $key;
            }
        }

        $ajustesAsignados = 0.0;
        $resultado = [];

        foreach ($lineas as $key => $linea) {
            $subtotal = (float) $linea['subtotal'];
            $cantidad = (float) $linea['cantidad'];

            $ajusteLinea = round($ajusteTotal * ($subtotal / $netoTotal), 2);
            $ajustesAsignados += $ajusteLinea;

            $resultado[$key] = ['subtotal' => $subtotal, 'cantidad' => $cantidad, 'ajuste' => $ajusteLinea];
        }

        if ($keyMayorSubtotal !== null) {
            $residuo = round($ajusteTotal - $ajustesAsignados, 2);
            $filaMayor = $resultado[$keyMayorSubtotal];
            $resultado[$keyMayorSubtotal] = [
                'subtotal' => $filaMayor['subtotal'],
                'cantidad' => $filaMayor['cantidad'],
                'ajuste' => round($filaMayor['ajuste'] + $residuo, 2),
            ];
        }

        return array_map(
            fn (array $r): float => $r['cantidad'] > 0
                ? round(($r['subtotal'] + $r['ajuste']) / $r['cantidad'], 4)
                : 0.0,
            $resultado
        );
    }

    /**
     * Suma las percepciones de la compra cuyo tipo afecta costo, reparte
     * ese total (menos el descuento) proporcional al neto de cada línea, y
     * persiste `costo_final` en cada `PurchaseLine`. No toca
     * products.costo_ultimo ni crea stock_movements — eso lo hace
     * Purchase::aumentarStock() después de llamar acá.
     */
    public function aplicar(Purchase $purchase): void
    {
        $impuestosQueAfectanCosto = $purchase->perceptions
            ->filter(fn (PurchasePerception $perception): bool => (bool) $perception->perceptionType?->afecta_costo)
            ->sum(fn (PurchasePerception $perception): float => (float) $perception->monto);

        $lineas = $purchase->lines
            ->mapWithKeys(fn (PurchaseLine $line): array => [
                $line->id => ['cantidad' => (float) $line->cantidad, 'subtotal' => (float) $line->subtotal],
            ])
            ->all();

        $costosFinales = $this->distribuir($lineas, $impuestosQueAfectanCosto, (float) $purchase->descuento);

        foreach ($purchase->lines as $line) {
            $line->update(['costo_final' => $costosFinales[$line->id] ?? (float) $line->costo_unit]);
        }
    }
}
