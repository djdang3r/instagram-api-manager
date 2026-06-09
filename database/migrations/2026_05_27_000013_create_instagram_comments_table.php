<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('comment_id')->unique();
            $table->ulid('instagram_business_account_id')->index();
            $table->string('instagram_media_id')->index();
            $table->string('instagram_user_id')->index();
            $table->text('text');
            $table->string('username');
            $table->string('profile_picture_url')->nullable();
            $table->timestamp('created_time');
            $table->string('message_type')->default('comment')->comment('comment, reply');
            $table->string('parent_comment_id')->nullable()->index()->comment('For replies');
            $table->integer('like_count')->default(0);
            $table->timestamp('hidden_at')->nullable()->comment('When comment was hidden by admin');
            $table->timestamp('deleted_at')->nullable()->comment('When comment was deleted');
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['instagram_media_id', 'created_time']);
            $table->foreign('instagram_business_account_id')
                ->references('id')->on('instagram_business_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_comments');
    }
};