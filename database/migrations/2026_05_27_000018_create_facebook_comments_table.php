<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('comment_id')->unique();
            $table->string('page_id')->index();
            $table->string('post_id')->index();
            $table->string('parent_id')->nullable()->index()->comment('For replies');
            $table->string('message')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->timestamp('created_time')->nullable();
            $table->integer('like_count')->default(0);
            $table->timestamp('hidden_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['post_id', 'created_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_comments');
    }
};