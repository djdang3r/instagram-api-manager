<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('instagram_account_id');
            $table->string('conversation_id')->unique();
            $table->string('instagram_user_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('instagram_account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('instagram_conversations');
    }
}