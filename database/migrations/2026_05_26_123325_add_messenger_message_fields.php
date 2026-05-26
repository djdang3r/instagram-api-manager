<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('json_content');
            $table->json('reactions')->nullable()->after('attachments');
            $table->string('caption')->nullable()->after('reactions');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('created_time')->nullable()->after('failed_at');
            $table->text('message_context')->nullable()->after('message_content');
            $table->string('message_context_id')->nullable()->after('message_context');
            $table->string('quick_reply_payload', 1000)->nullable()->after('message_context_id');
            $table->string('postback_payload', 1000)->nullable()->after('quick_reply_payload');
            $table->string('template_payload', 1000)->nullable()->after('postback_payload');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropColumn([
                'attachments', 'reactions', 'caption', 'delivered_at', 'created_time',
                'message_context', 'message_context_id', 'quick_reply_payload',
                'postback_payload', 'template_payload',
            ]);
        });
    }
};
