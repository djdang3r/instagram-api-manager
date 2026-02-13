<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instagram_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            $table->string('instagram_user_id');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->text('profile_picture')->nullable();
            $table->unsignedBigInteger('follows_count')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_user_follow_business')->default(false);
            $table->boolean('is_business_follow_user')->default(false);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('profile_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instagram_business_account_id')
                ->references('instagram_business_account_id')
                ->on('instagram_business_accounts')
                ->onDelete('cascade');

            $table->unique(['instagram_business_account_id', 'instagram_user_id'], 'instagram_contact_unique');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_contacts');
    }
};