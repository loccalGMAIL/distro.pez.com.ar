<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libro mayor de stock: única fuente de verdad.
        // El stock actual es SUM(quantity) filtrando por producto (y depósito).
        // No hay tabla de saldos en el MVP porque es 100% derivable: cuando el
        // volumen lo pida se agrega stock_balances como caché, sin migrar nada.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('lot_id')->nullable(); // reservado: lotes

            // Positivo = entrada, negativo = salida. Un solo signo, sin campos separados.
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4)->default(0);

            $table->enum('type', [
                'compra',        // ingreso por purchase confirmada
                'venta',         // egreso por sale confirmada
                'ajuste',        // conteo / corrección manual
                'devolucion',    // devolución de cliente (ingreso)
                'devolucion_prov', // devolución a proveedor (egreso)
                'merma',         // rotura, vencimiento
                'transferencia', // reservado para multi-depósito
            ]);

            // Polimórfico al documento que lo originó (purchase, sale, adjustment…).
            // Permite trazar cada unidad hasta su comprobante y revertir por anulación.
            $table->nullableMorphs('source'); // source_type + source_id

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('motivo')->nullable(); // obligatorio a nivel app para ajuste/merma
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
