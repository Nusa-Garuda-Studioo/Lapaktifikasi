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
        Schema::create('tbl_telegram_message_map', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id');
            $table->bigInteger('telegram_message_id');
            $table->foreignId('laporan_id')->constrained('tbl_laporan')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['chat_id', 'telegram_message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_telegram_message_map');
    }
};

