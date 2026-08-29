<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:recalculate-supplier-balances')]
#[Description('Recalcula Purchase.saldo y Supplier.balance desde compras confirmadas y pagos imputados')]
class RecalculateSupplierBalances extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Purchase::where('status', 'confirmada')->each(fn (Purchase $purchase) => $purchase->recalcularSaldo());
        Supplier::each(fn (Supplier $supplier) => $supplier->recalcularBalance());

        $this->info('Listo.');
    }
}
