<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:recalculate-supplier-balances')]
#[Description('Recalcula Payment.sin_imputar, Purchase.saldo y Supplier.balance desde compras confirmadas y pagos imputados')]
class RecalculateSupplierBalances extends Command
{
    /**
     * Execute the console command.
     *
     * Orden importa: sin_imputar antes que saldo/balance porque
     * Supplier::recalcularBalance() ahora resta sin_imputar del saldo
     * (saldo a favor de un pago no imputado).
     */
    public function handle(): void
    {
        Payment::each(fn (Payment $payment) => $payment->recalcularSinImputar());
        Purchase::where('status', 'confirmada')->each(fn (Purchase $purchase) => $purchase->recalcularSaldo());
        Supplier::each(fn (Supplier $supplier) => $supplier->recalcularBalance());

        $this->info('Listo.');
    }
}
