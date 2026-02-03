<?php

// ---------------------------------------------------------
// 1. FAYL: database/migrations/xxxx_xx_xx_create_product_batches_table.php
// Terminalda: php artisan make:migration create_product_batches_table
// ---------------------------------------------------------

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();

            // Hansı məhsula aiddir?
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');

            // Maya Dəyəri (Bu partiya neçəyə gəlib?)
            $table->decimal('cost_price', 10, 2);

            // Miqdar (Nə qədər gəlib və nə qədər qalıb)
            $table->integer('initial_quantity'); // İlk gələn say (Tarixçə üçün)
            $table->integer('current_quantity'); // Hazırda qalan say (Satıldıqca azalacaq)

            // Təchizatçı (Kimdən almışıq?) - Gələcək üçün
            // $table->foreignId('supplier_id')->nullable();

            // Qeyd (Məs: Qaimə nömrəsi)
            $table->string('batch_code')->nullable();

            // Son istifadə tarixi (Ərzaq üçün vacibdir)
            $table->date('expiration_date')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_batches');
    }
};
