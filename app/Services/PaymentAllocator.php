<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PaymentAllocator
{
    /**
     * Reparte lo que le quede sin imputar a un pago de egreso a proveedor
     * entre sus compras confirmadas con saldo, de la más vieja a la más
     * nueva (FIFO por fecha, después por id para desempatar), hasta agotar
     * el monto disponible. El excedente queda como saldo a favor
     * (`Payment::sin_imputar`), que `Supplier::recalcularBalance()` resta
     * de la deuda.
     *
     * Solo reparte el remanente (monto − ya imputado), así que es
     * idempotente: correrlo de nuevo sobre un pago ya imputado, o tras
     * subirle el monto, no duplica nada.
     */
    public function allocate(Payment $payment): void
    {
        if ($payment->direccion !== 'egreso' || ! $payment->party instanceof Supplier) {
            return;
        }

        $disponible = (float) $payment->monto - (float) $payment->allocations()->sum('monto');

        if ($disponible <= 0) {
            return;
        }

        $supplier = $payment->party;

        DB::transaction(function () use ($payment, $supplier, $disponible) {
            $restante = $disponible;

            $purchases = $supplier->purchases()
                ->where('status', 'confirmada')
                ->where('saldo', '>', 0)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get();

            foreach ($purchases as $purchase) {
                if ($restante <= 0) {
                    break;
                }

                $monto = min($restante, (float) $purchase->saldo);

                $payment->allocations()->create([
                    'allocatable_type' => Purchase::class,
                    'allocatable_id' => $purchase->id,
                    'monto' => $monto,
                ]);

                $restante -= $monto;
            }
        });

        // Necesario incluso sin ninguna allocation nueva (pago ya cubierto,
        // o sin compras pendientes): el hook de PaymentAllocation no corre
        // en ese caso y sin_imputar/balance quedarían desactualizados.
        $payment->recalcularSinImputar();
        $supplier->recalcularBalance();
    }
}
