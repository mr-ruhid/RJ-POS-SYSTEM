<?php

// ---------------------------------------------------------
// 1. FAYL: database/migrations/xxxx_xx_xx_create_orders_table.php
// Terminalda: php artisan make:migration create_orders_table
// ---------------------------------------------------------

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Satış ID-si (UUID)

            // Kim satıb və harada satıb?
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Kassir
            $table->foreignId('cash_register_id')->nullable()->constrained()->onDelete('set null'); // Kassa nöqtəsi

            // Müştəri (Opsional)
            // $table->foreignId('customer_id')->nullable();

            // Qəbz Nömrəsi (Qısa və oxunaqlı)
            $table->string('receipt_code')->unique();

            // Maliyyə
            $table->decimal('subtotal', 10, 2); // Endirimsiz, vergisiz məbləğ
            $table->decimal('total_discount', 10, 2)->default(0); // Ümumi endirim
            $table->decimal('total_tax', 10, 2)->default(0); // Ümumi vergi
            $table->decimal('grand_total', 10, 2); // Yekun ödəniləcək məbləğ
            $table->decimal('total_cost', 10, 2)->default(0); // Ümumi Maya dəyəri (Mənfəət hesabı üçün)

            // Ödəniş
            $table->decimal('paid_amount', 10, 2); // Müştərinin verdiyi pul
            $table->decimal('change_amount', 10, 2)->default(0); // Qaytarılan qalıq (sdat)
            $table->string('payment_method')->default('cash'); // cash, card, mixed

            $table->enum('status', ['completed', 'refunded', 'cancelled'])->default('completed');

            $table->timestamps();
        });

        // ---------------------------------------------------------
        // Satışın içindəki məhsullar (Order Items)
        // ---------------------------------------------------------
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products'); // Məhsul silinsə də satışda qalmalıdır

            // Məhsulun o anki məlumatları (Qiymət dəyişsə belə, satış anındakı qalmalıdır)
            $table->string('product_name');
            $table->string('product_barcode');

            $table->integer('quantity');

            // Maliyyə (1 ədəd üçün)
            $table->decimal('price', 10, 2); // Satış qiyməti
            $table->decimal('cost', 10, 2); // Maya dəyəri (FIFO-dan hesablanacaq)
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->decimal('total', 10, 2); // (price - discount + tax) * quantity

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
