<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // role_id sütunu əlavə edirik (default olaraq null ola bilər)
            // constrained() - avtomatik roles cədvəlinə bağlayır
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->onDelete('set null');

            // İstifadəçinin aktiv olub olmadığını yoxlamaq üçün
            $table->boolean('is_active')->default(true)->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'is_active']);
        });
    }
};
