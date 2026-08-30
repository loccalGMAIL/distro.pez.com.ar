<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ivaTypeId = DB::table('perception_types')->where('nombre', 'IVA 21%')->value('id')
            ?? DB::table('perception_types')->insertGetId([
                'nombre' => 'IVA 21%',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('purchases')->where('iva', '>', 0)->get(['id', 'iva'])->each(function (object $purchase) use ($ivaTypeId) {
            DB::table('purchase_perceptions')->insert([
                'purchase_id' => $purchase->id,
                'perception_type_id' => $ivaTypeId,
                'descripcion' => 'IVA (migrado)',
                'monto' => $purchase->iva,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::statement('UPDATE purchases SET percepciones = percepciones + iva WHERE iva > 0');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('iva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('iva', 14, 2)->default(0)->after('descuento');
        });
    }
};
