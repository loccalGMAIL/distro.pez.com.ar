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
        Schema::table('products', function (Blueprint $table) {
            // Último costo de factura sin impuestos (referencia/auditoría).
            // costo_ultimo pasa a ser el costo final con impuestos
            // prorrateados — ver Purchase::aumentarStock y
            // PurchaseCostAllocator.
            $table->decimal('costo_neto_ultimo', 14, 4)->default(0)->after('costo_ultimo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('costo_neto_ultimo');
        });
    }
};
