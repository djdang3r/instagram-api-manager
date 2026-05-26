<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_contacts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('messenger_user_id');
            $table->timestamp('last_interaction_at')->nullable()->after('profile_picture');
            $table->timestamp('profile_synced_at')->nullable()->after('last_interaction_at');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_contacts', function (Blueprint $table) {
            $table->dropColumn(['name', 'last_interaction_at', 'profile_synced_at']);
        });
    }
};
