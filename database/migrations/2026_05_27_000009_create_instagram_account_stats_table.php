<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_account_stats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('instagram_business_account_id')->index();
            $table->date('date')->index();
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->integer('total_messages_sent')->default(0);
            $table->integer('total_messages_received')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_followers_gained')->default(0);
            $table->integer('total_followers_lost')->default(0);
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['instagram_business_account_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_account_stats');
    }
};