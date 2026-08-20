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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social')->nullable();
            $table->string('cuit')->nullable();
            $table->enum('condicion_iva', [
                'responsable_inscripto',
                'monotributo',
                'exento',
                'consumidor_final',
                'no_responsable',
            ])->default('responsable_inscripto');
            $table->string('domicilio_fiscal')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('punto_venta', 10)->nullable();
            $table->unsignedInteger('proximo_numero_comprobante')->default(1);
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
