<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Məs: store_name
            $table->text('value')->nullable(); // Məs: "RJ Market"
            $table->timestamps();
        });

        // İlkin məlumatları dərhal əlavə edək (Seeder-ə ehtiyac qalmasın)
        DB::table('settings')->insert([
            ['key' => 'store_name', 'value' => 'RJ POS Market', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_address', 'value' => 'Bakı şəhəri, Mərkəz küçəsi 1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_phone', 'value' => '+994 50 000 00 00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'receipt_footer', 'value' => 'Bizi seçdiyiniz üçün təşəkkürlər!', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
