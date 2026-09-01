<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // NULL = pendiente de liquidar. Es la fuente de verdad del
            // "contador" del reporte: no se denormaliza ningún saldo.
            $table->foreignId('time_entry_settlement_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();

            // Tarifa congelada al liquidar. Sin esto, cambiar la tarifa del
            // usuario descuadra el detalle de un recibo ya emitido contra su
            // total (que sí queda congelado en la liquidación).
            $table->decimal('tarifa_hora', 14, 2)->nullable()->after('ended_at');

            $table->index(['user_id', 'time_entry_settlement_id']);
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'time_entry_settlement_id']);
            $table->dropConstrainedForeignId('time_entry_settlement_id');
            $table->dropColumn('tarifa_hora');
        });
    }
};
