<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('instagram_contacts', 'name')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->string('name')->nullable()->after('username');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'last_interaction_at')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->timestamp('last_interaction_at')->nullable()->after('profile_picture');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'is_verified_user')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->boolean('is_verified_user')->nullable()->after('last_interaction_at');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'follower_count')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->unsignedInteger('follower_count')->nullable()->after('is_verified_user');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'is_user_follow_business')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->boolean('is_user_follow_business')->nullable()->after('follower_count');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'is_business_follow_user')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->boolean('is_business_follow_user')->nullable()->after('is_user_follow_business');
            });
        }

        if (!Schema::hasColumn('instagram_contacts', 'profile_synced_at')) {
            Schema::table('instagram_contacts', function (Blueprint $table) {
                $table->timestamp('profile_synced_at')->nullable()->after('is_business_follow_user');
            });
        }

        if (Schema::hasColumn('instagram_conversations', 'conversation_id')) {
            Schema::table('instagram_conversations', function (Blueprint $table) {
                $table->string('conversation_id')->nullable()->change();
            });
        }
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

        if (Schema::hasColumn('instagram_conversations', 'conversation_id')) {
            Schema::table('instagram_conversations', function (Blueprint $table) {
                $table->string('conversation_id')->nullable(false)->change();
            });
        }
    }
};
