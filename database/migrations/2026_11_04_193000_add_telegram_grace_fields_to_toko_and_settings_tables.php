<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_toko') && !Schema::hasColumn('tbl_toko', 'telegram_grace_due_at')) {
            Schema::table('tbl_toko', function (Blueprint $table) {
                $table->timestamp('telegram_grace_due_at')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('setting_websites') && !Schema::hasColumn('setting_websites', 'telegram_seller_grace_days')) {
            Schema::table('setting_websites', function (Blueprint $table) {
                $table->unsignedInteger('telegram_seller_grace_days')->default(14)->after('is_pakasir_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_toko') && Schema::hasColumn('tbl_toko', 'telegram_grace_due_at')) {
            Schema::table('tbl_toko', function (Blueprint $table) {
                $table->dropColumn('telegram_grace_due_at');
            });
        }

        if (Schema::hasTable('setting_websites') && Schema::hasColumn('setting_websites', 'telegram_seller_grace_days')) {
            Schema::table('setting_websites', function (Blueprint $table) {
                $table->dropColumn('telegram_seller_grace_days');
            });
        }
    }
};

