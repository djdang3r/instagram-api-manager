<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_referrals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('messenger_user_id');
            $table->string('page_id');
            $table->string('ref_parameter')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('messenger_conversations')->onDelete('cascade');
            $table->foreign('page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_referrals');
    }
};
