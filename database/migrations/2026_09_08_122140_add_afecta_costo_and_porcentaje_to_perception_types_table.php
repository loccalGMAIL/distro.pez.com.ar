<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('perception_types', function (Blueprint $table) {
            // Los tipos existentes quedan en true a propósito: hoy ninguna
            // percepción suma al costo del producto (ver
            // Purchase::aumentarStock), prenderlas es justamente el fix.
            // Quien recupere el IVA como crédito fiscal lo apaga acá.
            $table->boolean('afecta_costo')->default(true)->after('activo');
            $table->decimal('porcentaje', 6, 3)->nullable()->after('afecta_costo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perception_types', function (Blueprint $table) {
            $table->dropColumn(['afecta_costo', 'porcentaje']);
        });
    }
};
