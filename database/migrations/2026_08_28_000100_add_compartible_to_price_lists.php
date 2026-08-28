<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            // Controla si la lista aparece en el botón "Compartir lista de
            // precios" (y si su PDF es accesible). Independiente de `activo`:
            // una lista puede seguir usándose para vender sin ser compartible.
            $table->boolean('compartible')->default(true)->after('predeterminada');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn('compartible');
        });
    }
};
