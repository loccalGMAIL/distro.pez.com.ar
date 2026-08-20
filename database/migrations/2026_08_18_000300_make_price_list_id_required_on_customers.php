<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['price_list_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('price_list_id')->nullable(false)->change();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['price_list_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('price_list_id')->nullable()->change();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullOnDelete();
        });
    }
};
