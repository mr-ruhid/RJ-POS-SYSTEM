<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Order Items cədvəlinə "qaytarılan say" əlavə edirik
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('returned_quantity')->default(0)->after('quantity');
        });

        // 2. Orders cədvəlinə "qaytarılan məbləğ" və status əlavə edirik
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('grand_total');
            // Status enum-a 'partial_refunded' (hissəvi qaytarma) əlavə etmək üçün
            // Laravel-də enum dəyişmək çətin olduğu üçün sadəcə string kimi saxlayırıq və ya kodda idarə edirik.
            // Mövcud 'status' sütununa 'refunded' artıq var idi, biz əlavə olaraq 'partial_refunded' məntiqini kodda işlədəcəyik.
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });
    }
};
