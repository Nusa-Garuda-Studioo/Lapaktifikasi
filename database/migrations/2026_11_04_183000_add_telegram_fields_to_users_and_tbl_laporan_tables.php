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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('telegram_chat_id')->nullable()->unique()->after('remember_token');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');
            $table->timestamp('telegram_blocked_at')->nullable()->after('telegram_linked_at');
            $table->string('notif_channel_laporan')->default('auto')->after('telegram_blocked_at');
        });

        Schema::table('tbl_laporan', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable()->after('status');
            $table->timestamp('sla_due_at')->nullable()->index()->after('first_response_at');
            $table->timestamp('escalated_at')->nullable()->after('sla_due_at');
            $table->bigInteger('telegram_topic_id')->nullable()->after('escalated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_laporan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropIndex(['sla_due_at']);
            $table->dropColumn([
                'first_response_at',
                'sla_due_at',
                'escalated_at',
                'telegram_topic_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_linked_at',
                'telegram_blocked_at',
                'notif_channel_laporan',
            ]);
        });
    }
};

