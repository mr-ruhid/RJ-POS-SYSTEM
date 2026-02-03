<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Çek nömrəsindən sonra unikal lotoreya kodu əlavə edirik
            $table->string('lottery_code')->nullable()->unique()->after('receipt_code');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('lottery_code');
        });
    }
};
