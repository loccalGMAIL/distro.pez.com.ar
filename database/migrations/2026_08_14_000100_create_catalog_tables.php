<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Depósitos. En el MVP se crea uno solo ("Depósito principal"),
        // pero la FK ya existe en todos los movimientos: sumar depósitos
        // o camiones más adelante no requiere tocar el resto del esquema.
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', ['deposito', 'camion', 'sucursal'])->default('deposito');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique(); // lectura en venta mobile
            $table->string('nombre');
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();

            // Unidad base. Cuando entren bultos/packs se agrega product_units
            // y las líneas ya tienen product_unit_id nullable esperando.
            $table->string('base_unit', 20)->default('unidad');

            $table->decimal('precio_venta', 14, 2)->default(0);
            $table->decimal('costo_ultimo', 14, 4)->default(0); // se actualiza en cada compra
            $table->decimal('min_stock', 12, 3)->default(0);

            // Reservado para fase 2 (trazabilidad de lotes / vencimientos).
            $table->boolean('tracks_lot')->default(false);

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('warehouses');
    }
};
