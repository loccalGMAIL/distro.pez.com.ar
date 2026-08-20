<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            // nullable: permite venta de mostrador sin cliente cargado.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('numero', 40)->nullable()->unique();
            $table->date('fecha');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // En el MVP toda venta es contado, así que saldo queda en 0.
            // La columna existe para que activar cta cte de clientes sea
            // solo habilitar la lógica, sin migración de datos.
            $table->decimal('saldo', 14, 2)->default(0);
            $table->enum('condicion_pago', ['contado', 'cuenta_corriente'])->default('contado');
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'mercadopago', 'otro'])
                ->nullable();

            // confirmada descuenta stock; anulada lo devuelve.
            $table->enum('status', ['borrador', 'confirmada', 'anulada'])->default('borrador');

            // Envío del comprobante por WhatsApp.
            $table->string('comprobante_path')->nullable();
            $table->timestamp('enviado_at')->nullable();

            // Reservados para el esquema completo (pedido -> remito -> reparto).
            $table->unsignedBigInteger('customer_address_id')->nullable();
            $table->unsignedBigInteger('delivery_note_id')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('fecha');
        });

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->unsignedBigInteger('product_unit_id')->nullable(); // reservado
            $table->unsignedBigInteger('lot_id')->nullable();          // reservado

            $table->decimal('cantidad', 12, 3);
            $table->decimal('precio_unit', 14, 2);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);

            // Costo congelado al momento de la venta, para margen por línea
            // sin depender del costo actual del producto.
            $table->decimal('costo_unit', 14, 4)->default(0);

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
    }
};
