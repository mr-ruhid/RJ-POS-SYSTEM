<?php

// ---------------------------------------------------------
// 1. FAYL: database/migrations/xxxx_xx_xx_create_cash_registers_table.php
// Terminalda: php artisan make:migration create_cash_registers_table
// ---------------------------------------------------------

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Məs: Kassa 1, Ana Kassa
            $table->string('code')->unique(); // Məs: POS-01 (Sistem daxili kod)
            $table->string('ip_address')->nullable(); // Əgər IP printer varsa

            // Maliyyə
            $table->decimal('balance', 10, 2)->default(0); // Kassadakı cari nəğd pul

            // Statuslar
            $table->enum('status', ['open', 'closed'])->default('closed'); // Növbə açıqdır/bağlıdır
            $table->boolean('is_active')->default(true); // Kassa işləkdirmi?

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_registers');
    }
};

