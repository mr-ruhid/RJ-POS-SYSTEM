<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Məs: Nəğd, Kapital Bank Terminalı
            $table->string('slug')->unique(); // cash, card_kapital
            $table->enum('type', ['cash', 'card', 'other'])->default('card');

            // Terminal İnteqrasiyası üçün
            $table->boolean('is_integrated')->default(false); // Terminala qoşulub?
            $table->string('driver_name')->nullable(); // Məs: ingenico, pax
            $table->json('settings')->nullable(); // IP, Port, COM port məlumatları

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Standart metodları əlavə edək
        DB::table('payment_methods')->insert([
            [
                'name' => 'Nəğd',
                'slug' => 'cash',
                'type' => 'cash',
                'is_integrated' => false,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Bank Kartı (Terminal)',
                'slug' => 'card',
                'type' => 'card',
                'is_integrated' => false, // Sonra true edəcəyik
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
