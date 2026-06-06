<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_stories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('story_id')->unique();
            $table->ulid('instagram_business_account_id')->index();
            $table->string('media_id')->nullable()->index();
            $table->string('media_type')->default('image')->comment('image, video');
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('When story expires (24h)');
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('replies_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->string('status')->default('active')->comment('active, expired, highlighted');
            $table->json('mentions')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['instagram_business_account_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_stories');
    }
};