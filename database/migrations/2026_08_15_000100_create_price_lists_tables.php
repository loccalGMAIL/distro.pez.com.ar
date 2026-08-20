<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->nullable()->unique();

            // Recargo aplicado sobre costo_ultimo para calcular el precio de venta
            // de cualquier producto en esta lista: precio = costo * (1 + porcentaje/100).
            $table->decimal('porcentaje', 6, 2)->default(0);

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
