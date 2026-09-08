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
        Schema::table('purchase_perceptions', function (Blueprint $table) {
            // Porcentaje impreso en esta factura para esta percepción
            // (ej. "IVA 10,5%"). Autocompleta el monto al cargar a mano y
            // sirve para verificar que monto ≈ neto × %; la distribución
            // real al costo de las líneas es proporcional al neto, no por
            // este porcentaje (ver PurchaseCostAllocator).
            $table->decimal('porcentaje', 6, 3)->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_perceptions', function (Blueprint $table) {
            $table->dropColumn('porcentaje');
        });
    }
};
