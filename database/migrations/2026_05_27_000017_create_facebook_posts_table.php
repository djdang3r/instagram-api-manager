<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('post_id')->unique();
            $table->string('page_id')->index();
            $table->string('message')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('created_time')->nullable();
            $table->timestamp('updated_time')->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0)->comment('For video posts');
            $table->string('status')->default('published')->comment('published, scheduled, failed');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('type')->nullable()->comment('video, photo, link, status');
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
        Schema::dropIfExists('facebook_posts');
    }
};