<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_media', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('media_id')->unique();
            $table->string('facebook_post_id')->nullable()->index();
            $table->string('page_id')->index();
            $table->string('media_type')->comment('photo, video, link');
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('permalink')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_time')->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['page_id', 'created_time']);
            $table->foreign('page_id')
                ->references('page_id')->on('facebook_pages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_media');
    }
};