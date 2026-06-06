<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_media_stats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_media_id')->index();
            $table->ulid('instagram_business_account_id')->index();
            $table->date('date')->index();
            $table->integer('impressions')->default(0);
            $table->integer('reach')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('video_views')->default(0)->comment('For video content only');
            $table->integer('profile_visits')->default(0);
            $table->integer('follows')->default(0);
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['instagram_media_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_media_stats');
    }
};