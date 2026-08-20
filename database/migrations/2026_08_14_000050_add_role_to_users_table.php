<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles con un enum en el MVP. Si más adelante hace falta granularidad
        // (permisos por acción, accesos por depósito), entra spatie/permission
        // y este campo se usa para sembrar los roles iniciales.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'vendedor', 'deposito', 'chofer'])
                ->default('vendedor')
                ->after('email');
            $table->boolean('activo')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'activo']);
        });
    }
};
