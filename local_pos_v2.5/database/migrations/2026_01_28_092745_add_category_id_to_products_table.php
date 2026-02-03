<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Məhsullar cədvəlinə category_id əlavə edirik
            // after('description') - Təsvir sütunundan sonra gəlsin
            $table->foreignId('category_id')->nullable()->after('description')->constrained('categories')->onDelete('set null');

            // Həmçinin məhsulun şəkli də olmalıdır (Kassa üçün vacibdir)
            $table->string('image')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'image']);
        });
    }
};
