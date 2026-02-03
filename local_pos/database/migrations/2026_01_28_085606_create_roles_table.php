<?php

// ---------------------------------------------------------
// BU FAYLDA 3 HİSSƏ VAR. HƏRƏSİNİ AYRI FAYL KİMİ YARAT.
// ---------------------------------------------------------

// 1. FAYL: database/migrations/xxxx_xx_xx_create_roles_table.php
// Terminalda: php artisan make:migration create_roles_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Məsələn: "Mağaza Müdiri" (Görünən ad)
            $table->string('slug')->unique(); // Məsələn: "admin" (Kod tərəfdə işlədəcəyimiz)
            $table->text('permissions')->nullable(); // İcazələr (JSON formatında saxlayacağıq)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
