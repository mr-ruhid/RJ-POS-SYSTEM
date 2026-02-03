<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');

            // Endirim növü: 'fixed' (Məbləğ) və ya 'percent' (Faiz)
            $table->enum('type', ['fixed', 'percent'])->default('fixed');

            // Endirim dəyəri (məs: 5 manat və ya 10 faiz)
            $table->decimal('value', 10, 2);

            // Tarixlər
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // Aktivlik statusu
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_discounts');
    }
};
