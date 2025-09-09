<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('message_id');
            $table->enum('message_method', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply', 'quick_reply', 'generic_template', 'generic_template_action']);
            $table->string('message_from', 45);
            $table->string('message_to', 45);
            $table->text('message_content')->nullable();
            $table->text('message_context')->nullable();
            $table->string('message_context_id')->nullable();
            $table->string('message_context_from')->nullable();
            $table->json('attachments')->nullable();
            $table->text('caption')->nullable();
            $table->text('media_url')->nullable();
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
            $table->timestamp('created_time')->nullable();
            $table->boolean('is_unsupported')->default(false);
            $table->json('reactions')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('instagram_conversations')
                ->onDelete('cascade');

            $table->unique('message_id');
            $table->index('message_method');
            $table->index('status');
            $table->index('created_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_messages');
    }
};