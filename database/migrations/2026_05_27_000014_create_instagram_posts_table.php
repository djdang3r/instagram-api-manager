<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('media_id')->unique();
            $table->ulid('instagram_business_account_id')->index();
            $table->string('caption')->nullable();
            $table->string('media_type')->comment('IMAGE, VIDEO, CAROUSEL, REELS, STORY');
            $table->string('media_url')->nullable();
            $table->string('permalink')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->string('username')->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->string('status')->default('published')->comment('published, scheduled, failed');
            $table->timestamp('scheduled_at')->nullable()->comment('For scheduled posts');
            $table->timestamp('published_at')->nullable();
            $table->string('product_type')->nullable()->comment('feed, stories, reels, etc');
            $table->json('children_ids')->nullable()->comment('For carousel media');
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->foreign('instagram_business_account_id')
                ->references('id')->on('instagram_business_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};