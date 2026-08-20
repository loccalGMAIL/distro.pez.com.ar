<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('base_unit', [
                'unidad',
                'kg',
                'gramo',
                'litro',
                'mililitro',
                'docena',
                'caja',
                'bulto',
                'paquete',
                'metro',
            ])->default('unidad')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('base_unit', 20)->default('unidad')->change();
        });
    }
};
