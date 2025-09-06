<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramConversationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            $table->string('conversation_id')->unique();
            $table->string('instagram_user_id');
            $table->timestamp('last_message_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instagram_business_account_id')->references('instagram_business_account_id')->on('instagram_business_accounts')->onDelete('cascade');

            $table->index('instagram_user_id');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_conversations');
    }
}