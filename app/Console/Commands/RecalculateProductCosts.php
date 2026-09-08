<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Purchase;
use App\Services\PurchaseCostAllocator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:recalculate-product-costs {--dry-run : Muestra qué productos cambiarían, sin escribir nada}')]
#[Description('Recalcula purchase_lines.costo_final (impuestos prorrateados) y deja en cada producto el costo final y neto de su compra confirmada más reciente')]
class RecalculateProductCosts extends Command
{
    /**
     * Execute the console command.
     *
     * Recorre las compras confirmadas de más vieja a más nueva prorrateando
     * de nuevo sus impuestos con PurchaseCostAllocator (por si el catálogo de
     * percepciones —afecta_costo— cambió desde que se cargaron), y deja en
     * cada producto el costo final/neto de su compra confirmada más
     * reciente. Todo corre dentro de una transacción: con --dry-run se
     * revierte al final, así que no queda nada escrito, ni siquiera
     * purchase_lines.costo_final.
     */
    public function handle(PurchaseCostAllocator $allocator): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $costoAntes = Product::pluck('costo_ultimo', 'id');

        DB::beginTransaction();

        Purchase::where('status', 'confirmada')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->each(fn (Purchase $purchase) => $allocator->aplicar($purchase));

        $ultimaLineaPorProducto = DB::table('purchase_lines')
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchase_id')
            ->where('purchases.status', 'confirmada')
            ->orderBy('purchases.fecha')
            ->orderBy('purchases.id')
            ->orderBy('purchase_lines.id')
            ->get(['purchase_lines.product_id', 'purchase_lines.costo_unit', 'purchase_lines.costo_final'])
            ->groupBy('product_id')
            ->map(fn ($lineas) => $lineas->last());

        foreach ($ultimaLineaPorProducto as $productId => $linea) {
            Product::whereKey($productId)->update([
                'costo_ultimo' => $linea->costo_final,
                'costo_neto_ultimo' => $linea->costo_unit,
            ]);
        }

        $cambios = Product::query()
            ->whereIn('id', $ultimaLineaPorProducto->keys())
            ->get(['id', 'nombre', 'costo_ultimo'])
            ->filter(fn (Product $product): bool => (float) $costoAntes[$product->id] !== (float) $product->costo_ultimo);

        $dryRun ? DB::rollBack() : DB::commit();

        if ($cambios->isEmpty()) {
            $this->info($dryRun ? 'Ningún producto cambiaría.' : 'Ningún producto cambió.');

            return;
        }

        $this->table(
            ['Producto', 'Costo final antes', 'Costo final después'],
            $cambios->map(fn (Product $product): array => [
                $product->nombre,
                number_format((float) $costoAntes[$product->id], 2, ',', '.'),
                number_format((float) $product->costo_ultimo, 2, ',', '.'),
            ])->all()
        );

        $this->info($dryRun
            ? "{$cambios->count()} producto(s) cambiarían (dry-run, no se escribió nada)."
            : "{$cambios->count()} producto(s) actualizados.");
    }
}
