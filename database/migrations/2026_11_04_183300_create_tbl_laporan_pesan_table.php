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
        Schema::create('tbl_laporan_pesan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('tbl_laporan')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role'); // customer, seller, admin, system
            $table->text('body')->nullable();
            $table->string('sumber')->default('web'); // web, telegram, system
            $table->string('lampiran')->nullable();
            $table->bigInteger('telegram_message_id')->nullable();
            $table->timestamps();

            $table->index(['laporan_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_pesan');
    }
};

