<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_media_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('media_id')->unique();
            $table->string('instagram_post_id')->nullable()->index();
            $table->ulid('instagram_business_account_id')->index();
            $table->string('media_type')->comment('IMAGE, VIDEO');
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('permalink')->nullable();
            $table->integer('sort_order')->default(0)->comment('For carousel ordering');
            $table->string('caption')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->foreign('instagram_business_account_id')
                ->references('id')->on('instagram_business_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_media_posts');
    }
};