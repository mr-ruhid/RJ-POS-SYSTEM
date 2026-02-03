<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Partnyorlar Cədvəli (Əgər yoxdursa yarat)
        if (!Schema::hasTable('partners')) {
            Schema::create('partners', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('telegram_chat_id')->nullable(); // Telegram ID
                $table->decimal('balance', 10, 2)->default(0); // Qazandığı komissiyalar
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Promokodlar Cədvəli (Əgər yoxdursa yarat)
        if (!Schema::hasTable('promocodes')) {
            Schema::create('promocodes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // Məs: SALE20, PARTNER10

                $table->enum('type', ['store', 'partner'])->default('store'); // Mağaza yoxsa Partnyor?
                $table->foreignId('partner_id')->nullable()->constrained('partners')->onDelete('cascade');

                // Endirim (Müştəri üçün)
                $table->enum('discount_type', ['fixed', 'percent'])->default('percent');
                $table->decimal('discount_value', 10, 2);

                // Komissiya (Partnyor üçün)
                $table->enum('commission_type', ['fixed', 'percent'])->default('percent');
                $table->decimal('commission_value', 10, 2)->default(0);

                $table->integer('usage_limit')->nullable(); // Neçə dəfə işlədilə bilər
                $table->integer('used_count')->default(0);
                $table->dateTime('expires_at')->nullable();

                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Orders Cədvəlinə Komissiya Sütunları (Əgər yoxdursa əlavə et)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'promo_code')) {
                $table->string('promo_code')->nullable()->after('lottery_code');
            }

            if (!Schema::hasColumn('orders', 'promocode_id')) {
                $table->foreignId('promocode_id')->nullable()->after('promo_code')->constrained('promocodes')->onDelete('set null');
            }

            if (!Schema::hasColumn('orders', 'total_commission')) {
                $table->decimal('total_commission', 10, 2)->default(0)->after('total_cost');
            }
        });
    }

    public function down()
    {
        // Geri qaytarma zamanı sütunları silirik
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'total_commission')) $table->dropColumn('total_commission');
                if (Schema::hasColumn('orders', 'promocode_id')) $table->dropForeign(['promocode_id']);
                if (Schema::hasColumn('orders', 'promocode_id')) $table->dropColumn('promocode_id');
                if (Schema::hasColumn('orders', 'promo_code')) $table->dropColumn('promo_code');
            });
        }

        Schema::dropIfExists('promocodes');
        Schema::dropIfExists('partners');
    }
};
