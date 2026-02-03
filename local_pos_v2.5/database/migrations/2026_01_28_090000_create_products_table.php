<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            // ID əvəzinə UUID istifadə edirik (Offline-first üçün)
            $table->uuid('id')->primary();

            $table->string('name'); // Məhsulun adı
            $table->string('barcode')->unique(); // Barkod
            $table->text('description')->nullable(); // Təsvir

            // Qiymətlər
            $table->decimal('cost_price', 10, 2); // Maya dəyəri
            $table->decimal('selling_price', 10, 2); // Satış qiyməti
            $table->decimal('tax_rate', 5, 2)->default(0); // Vergi dərəcəsi

            $table->boolean('is_active')->default(true);

            // Sinxronizasiya sütunu
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
