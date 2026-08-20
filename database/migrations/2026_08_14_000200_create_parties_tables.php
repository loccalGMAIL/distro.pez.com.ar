<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable()->unique();
            $table->string('razon_social');
            $table->string('cuit', 20)->nullable();
            $table->string('telefono', 40)->nullable();   // se usa para el envío por WhatsApp
            $table->string('email')->nullable();

            // En el MVP un solo domicilio embebido. Fase 2: customer_addresses + zones,
            // migrando estos campos a la primera dirección de cada cliente.
            $table->string('domicilio')->nullable();
            $table->string('localidad')->nullable();

            $table->enum('condicion_pago', ['contado', 'cuenta_corriente'])->default('contado');
            $table->decimal('balance', 14, 2)->default(0); // saldo cacheado, en 0 mientras todo sea contado

            // Reservado: cuando entren price_lists, este FK se llena.
            $table->unsignedBigInteger('price_list_id')->nullable();

            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('razon_social');
            $table->index('activo');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable()->unique();
            $table->string('razon_social');
            $table->string('cuit', 20)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('domicilio')->nullable();

            $table->enum('condicion_pago', ['contado', 'cuenta_corriente'])->default('cuenta_corriente');
            $table->integer('dias_pago')->default(0); // plazo para calcular vencimiento

            // Saldo cacheado = sum(facturas.total) - sum(pagos imputados).
            // Fuente de verdad = purchases + payments; esto es solo para listar rápido.
            $table->decimal('balance', 14, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('razon_social');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
