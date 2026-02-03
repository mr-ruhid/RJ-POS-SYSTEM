<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_batches', function (Blueprint $table) {
            // Əgər sütun yoxdursa, əlavə etsin
            if (!Schema::hasColumn('product_batches', 'location')) {
                // 'store' (Mağaza) və ya 'warehouse' (Anbar).
                // Default 'store' qoyuruq ki, mövcud mallar satışda görünsün.
                $table->string('location')->default('store')->after('current_quantity');

                // Performans üçün indeks əlavə edirik
                $table->index('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if (Schema::hasColumn('product_batches', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
