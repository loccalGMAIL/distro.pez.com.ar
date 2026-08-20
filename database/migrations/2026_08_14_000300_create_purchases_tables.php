<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En el MVP "purchase" = factura de compra que además ingresa stock.
        // El esquema completo separa purchase_orders -> goods_receipts -> supplier_invoices;
        // cuando eso llegue, esta tabla queda como la factura y se le cuelga
        // purchase_order_id / goods_receipt_id (ya reservados abajo).
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('tipo_comprobante', ['factura_a', 'factura_b', 'factura_c', 'remito', 'otro'])
                ->default('factura_a');
            $table->string('numero', 40)->nullable();
            $table->date('fecha');
            $table->date('vence_at')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('iva', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Cacheado: total - sum(payment_allocations). 0 = cancelada.
            $table->decimal('saldo', 14, 2)->default(0);

            // borrador: editable y sin impacto en stock ni en el saldo del proveedor.
            // confirmada: genera stock_movements y deuda. anulada: revierte.
            $table->enum('status', ['borrador', 'confirmada', 'anulada'])->default('borrador');

            // Módulo de IA de lectura de facturas: se guarda el archivo y el JSON extraído
            // para poder auditar / reprocesar lo que interpretó.
            $table->string('archivo_path')->nullable();
            $table->json('ocr_data')->nullable();

            // Reservados para el esquema completo.
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('goods_receipt_id')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'status']);
            $table->index('fecha');
            $table->unique(['supplier_id', 'tipo_comprobante', 'numero'], 'purchases_supplier_comprobante_unique');
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // Reservado: unidad de compra (bulto/pack) cuando entre product_units.
            $table->unsignedBigInteger('product_unit_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable(); // reservado: lotes

            $table->decimal('cantidad', 12, 3);
            $table->decimal('costo_unit', 14, 4);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchases');
    }
};
