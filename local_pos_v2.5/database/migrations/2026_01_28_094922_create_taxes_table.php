<?php

// ---------------------------------------------------------
// 1. FAYL: database/migrations/xxxx_xx_xx_create_taxes_table.php
// Terminalda: php artisan make:migration create_taxes_table
// ---------------------------------------------------------

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Məs: "ƏDV", "Sadələşdirilmiş"
            $table->decimal('rate', 5, 2); // Məs: 18.00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('taxes');
    }
};
