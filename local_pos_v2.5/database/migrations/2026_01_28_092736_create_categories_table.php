<?php

// ---------------------------------------------------------
// BU KODLARI 3 AYRI FAYLDA YARATMAQ LAZIMDIR
// ---------------------------------------------------------

// 1. FAYL: database/migrations/xxxx_xx_xx_create_categories_table.php
// Terminalda: php artisan make:migration create_categories_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Kateqoriya üçün adi ID kifayətdir

            $table->string('name'); // Məs: "İçkilər"
            $table->string('slug')->nullable(); // Məs: "ickiler"
            $table->text('image')->nullable(); // Kateqoriya şəkli (Kassa ekranı üçün)

            // Subkateqoriya məntiqi (Öz-özünə referans)
            // Əgər bu null-dursa, deməli ana kateqoriyadır.
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
