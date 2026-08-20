<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Gastos operativos: combustible, sueldos, alquiler, servicios.
        // Deliberadamente separado de purchases: acá no hay stock ni líneas.
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();

            // Opcional: si el gasto vino de un proveedor cargado. Un gasto
            // con supplier_id NO genera deuda en cuenta corriente (eso es purchases).
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->date('fecha');
            $table->string('descripcion');
            $table->decimal('monto', 14, 2);
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro'])
                ->default('efectivo');
            $table->string('comprobante_numero', 40)->nullable();
            $table->string('archivo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
            $table->index('expense_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
