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
        Schema::create('tbl_laporan_log', function (Blueprint $table) {
            $table->bigIncrements('id_log');
            $table->unsignedBigInteger('laporan_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->string('sumber_perubahan');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('laporan_id')
                ->references('id')
                ->on('tbl_laporan')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['laporan_id', 'created_at']);
        });

        if (Schema::hasTable('setting_websites') && !Schema::hasColumn('setting_websites', 'sla_respon_jam')) {
            Schema::table('setting_websites', function (Blueprint $table) {
                $table->unsignedInteger('sla_respon_jam')->default(3)->after('telegram_seller_grace_days');
            });
        }

        if (Schema::hasTable('tbl_laporan') && !Schema::hasColumn('tbl_laporan', 'sla_reminder_sent_at')) {
            Schema::table('tbl_laporan', function (Blueprint $table) {
                $table->timestamp('sla_reminder_sent_at')->nullable()->after('sla_due_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_laporan') && Schema::hasColumn('tbl_laporan', 'sla_reminder_sent_at')) {
            Schema::table('tbl_laporan', function (Blueprint $table) {
                $table->dropColumn('sla_reminder_sent_at');
            });
        }

        if (Schema::hasTable('setting_websites') && Schema::hasColumn('setting_websites', 'sla_respon_jam')) {
            Schema::table('setting_websites', function (Blueprint $table) {
                $table->dropColumn('sla_respon_jam');
            });
        }

        Schema::dropIfExists('tbl_laporan_log');
    }
};
