<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->enum('direction', ['incoming', 'outgoing'])->index();
            $table->string('message_type', 50)->default('text');
            $table->text('message')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('external_message_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
