<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_insights', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('page_id')->index();
            $table->date('date')->index();
            $table->integer('total_conversations')->default(0);
            $table->integer('total_messages_sent')->default(0);
            $table->integer('total_messages_received')->default(0);
            $table->integer('total_blocked_contacts')->default(0);
            $table->integer('total_reported_contacts')->default(0);
            $table->integer('page_views')->default(0);
            $table->integer('page_impressions')->default(0);
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['page_id', 'date']);
            $table->foreign('page_id')
                ->references('page_id')->on('facebook_pages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_insights');
    }
};