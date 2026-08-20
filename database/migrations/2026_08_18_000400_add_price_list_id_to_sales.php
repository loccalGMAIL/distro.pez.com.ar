<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Nullable: venta de mostrador puede no tener ninguna elegida
            // todavía (se autocompleta con la del cliente, pero es pisable).
            $table->foreignId('price_list_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('price_lists')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
        });
    }
};
