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
        // Memoria de correcciones humanas: cuando un usuario confirma qué
        // producto corresponde a una línea de factura de un proveedor dado,
        // se recuerda acá para que el próximo escaneo de ese mismo proveedor
        // ya venga vinculado (decisión humana > conjetura de la IA).
        Schema::create('supplier_product_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

            // Texto de la línea normalizado (minúsculas, sin acentos, trim),
            // usado como clave de búsqueda contra lo que devuelva la IA.
            $table->string('description_key');

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'description_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_product_links');
    }
};
