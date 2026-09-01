<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Liquidación de honorarios de fichaje: el comprobante que asienta el
        // pago de un período y saca esos ciclos de "pendiente de cobro".
        // Horas, tarifa y total se congelan acá: cambiar `users.hourly_rate`
        // después no puede reescribir un pago ya hecho.
        Schema::create('time_entry_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->date('periodo_desde');
            $table->date('periodo_hasta');

            $table->decimal('horas', 8, 2);
            $table->decimal('tarifa_hora', 14, 2);
            $table->decimal('total', 14, 2);

            $table->enum('status', ['confirmada', 'anulada'])->default('confirmada');

            $table->date('fecha_pago');
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'mercadopago', 'otro'])
                ->default('efectivo');
            $table->string('referencia', 80)->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('liquidated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entry_settlements');
    }
};
