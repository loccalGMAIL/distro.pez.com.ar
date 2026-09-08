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
        Schema::table('purchase_lines', function (Blueprint $table) {
            // Costo unitario con los impuestos de la factura que afectan
            // costo (PerceptionType.afecta_costo) prorrateados por neto —
            // ver PurchaseCostAllocator. Es lo que Purchase::aumentarStock
            // usa para actualizar products.costo_ultimo, no costo_unit.
            $table->decimal('costo_final', 14, 4)->default(0)->after('costo_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('costo_final');
        });
    }
};
