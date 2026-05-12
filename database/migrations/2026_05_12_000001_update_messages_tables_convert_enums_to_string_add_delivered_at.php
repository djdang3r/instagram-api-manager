<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->string('message_method', 10)->change();
            $table->string('message_type', 30)->index()->change();
            $table->string('status', 15)->default('pending')->change();
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
        });

        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->string('message_method', 10)->change();
            $table->string('message_type', 30)->index()->change();
            $table->string('status', 15)->default('pending')->change();
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->dropColumn('delivered_at');

            $table->enum('message_method', ['incoming', 'outgoing'])->change();
            $table->enum('message_type', ['text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply', 'quick_reply', 'generic_template', 'generic_template_action', 'default_action', 'postback_interaction', 'postback', 'button_template', 'image', 'document', 'file'])->change();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'received'])->default('pending')->change();
        });

        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropColumn('delivered_at');

            $table->enum('message_method', ['incoming', 'outgoing'])->change();
            $table->enum('message_type', ['text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply'])->change();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'received'])->default('pending')->change();
        });
    }
};
