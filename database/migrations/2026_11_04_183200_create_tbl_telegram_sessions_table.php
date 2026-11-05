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
        Schema::create('tbl_telegram_sessions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->index();
            $table->string('flow');
            $table->string('step');
            $table->json('payload')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_telegram_sessions');
    }
};

