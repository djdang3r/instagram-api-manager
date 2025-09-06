<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessengerMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('message_id')->unique();
            $table->enum('message_method', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply']);
            $table->string('message_from', 45);
            $table->string('message_to', 45);
            $table->text('message_content')->nullable();
            $table->json('json_content')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'received'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->integer('code_error')->nullable();
            $table->text('title_error')->nullable();
            $table->text('message_error')->nullable();
            $table->text('details_error')->nullable();
            $table->json('json')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('messenger_conversations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_messages');
    }
}