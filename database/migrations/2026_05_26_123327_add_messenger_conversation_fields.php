<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_conversations', function (Blueprint $table) {
            $table->integer('unread_count')->default(0)->after('last_message_at');
            $table->boolean('is_archived')->default(false)->after('unread_count');
            $table->timestamp('updated_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messenger_conversations', function (Blueprint $table) {
            $table->dropColumn(['unread_count', 'is_archived', 'updated_time']);
        });
    }
};
