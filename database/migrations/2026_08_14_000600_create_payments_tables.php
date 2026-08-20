<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pagos. Polimórfico a proveedor o cliente desde el día uno:
        // en el MVP solo se usa para proveedores (egresos), y habilitar
        // cobranzas de clientes después no requiere ninguna migración.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('party'); // party_type: Supplier|Customer, party_id
            $table->enum('direccion', ['egreso', 'ingreso'])->default('egreso');

            $table->date('fecha');
            $table->decimal('monto', 14, 2);
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro'])
                ->default('efectivo');
            $table->string('referencia', 80)->nullable(); // nro de cheque, CBU, comprobante

            // monto - sum(allocations). > 0 significa pago a cuenta sin imputar.
            $table->decimal('sin_imputar', 14, 2)->default(0);

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('comprobante_path')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
        });

        // Imputación de pagos a comprobantes. Es lo que resuelve las entregas
        // parciales: un pago puede cubrir parte de una factura, varias facturas,
        // o quedar a cuenta (sin ninguna fila acá).
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            // Polimórfico: hoy Purchase, mañana Sale sin tocar el esquema.
            $table->morphs('allocatable'); // allocatable_type, allocatable_id

            $table->decimal('monto', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
