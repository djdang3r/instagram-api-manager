<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('message_id')->unique();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply']);
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('instagram_conversations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_messages');
    }
}