<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            $table->string('conversation_id');
            $table->string('instagram_user_id');
            $table->json('senders')->nullable();
            $table->timestamp('updated_time')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instagram_business_account_id')
                ->references('instagram_business_account_id')
                ->on('instagram_business_accounts')
                ->onDelete('cascade');

            $table->unique('conversation_id');
            $table->index('instagram_user_id');
            $table->index('last_message_at');
            $table->index('updated_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_conversations');
    }
};