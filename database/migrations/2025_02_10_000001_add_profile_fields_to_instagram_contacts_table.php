<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_contacts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('username');
            $table->timestamp('last_interaction_at')->nullable()->after('profile_picture');
            $table->boolean('is_verified_user')->nullable()->after('last_interaction_at');
            $table->unsignedInteger('follower_count')->nullable()->after('is_verified_user');
            $table->boolean('is_user_follow_business')->nullable()->after('follower_count');
            $table->boolean('is_business_follow_user')->nullable()->after('is_user_follow_business');
            $table->timestamp('profile_synced_at')->nullable()->after('is_business_follow_user');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'last_interaction_at',
                'is_verified_user',
                'follower_count',
                'is_user_follow_business',
                'is_business_follow_user',
                'profile_synced_at',
            ]);
        });
    }
};
