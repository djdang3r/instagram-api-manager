<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_referrals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('conversation_id');
            $table->string('instagram_user_id');
            $table->string('instagram_business_account_id');
            $table->string('ref_parameter', 2083)->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('instagram_conversations')->onDelete('cascade');
            $table->foreign('instagram_business_account_id')->references('instagram_business_account_id')->on('instagram_business_accounts')->onDelete('cascade');
            
            $table->index('ref_parameter');
            $table->index('source');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_referrals');
    }
};