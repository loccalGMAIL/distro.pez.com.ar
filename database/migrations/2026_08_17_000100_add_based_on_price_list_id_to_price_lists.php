<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            // Permite encadenar listas: si se completa, el % de esta lista se
            // aplica sobre el precio ya calculado de la lista referenciada,
            // en vez de sobre costo_ultimo. restrictOnDelete evita borrar una
            // lista de la que otras dependen sin antes reasignarlas.
            $table->foreignId('based_on_price_list_id')
                ->nullable()
                ->after('codigo')
                ->constrained('price_lists')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('based_on_price_list_id');
        });
    }
};
