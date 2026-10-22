<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            // Además de `compartible`: habilita la lista para usuarios que no
            // tienen el permiso ShareAll:PriceList (ej. vendedores).
            $table->boolean('compartible_vendedores')->default(false)->after('compartible');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn('compartible_vendedores');
        });
    }
};
