<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessengerConversationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('page_id');
            $table->string('conversation_id')->unique();
            $table->string('messenger_user_id');
            $table->timestamp('last_message_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_conversations');
    }
}