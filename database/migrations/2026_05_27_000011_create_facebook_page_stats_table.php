<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_page_stats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('page_id')->index();
            $table->date('date')->index();
            $table->integer('page_followers')->default(0);
            $table->integer('page_likes')->default(0);
            $table->integer('total_ads_reach')->default(0)->comment('Sum of reach across all active ads');
            $table->integer('total_page_views')->default(0);
            $table->integer('total_page_impressions')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_received')->default(0);
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['page_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_page_stats');
    }
};