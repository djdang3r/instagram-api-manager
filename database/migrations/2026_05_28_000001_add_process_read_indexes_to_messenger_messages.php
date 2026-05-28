<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            // Soporta update por conversación + método + rango de created_time (read por MID objetivo).
            $table->index(
                ['conversation_id', 'message_method', 'created_time'],
                'msgr_msgs_conv_method_created_time_idx'
            );

            // Soporta update por conversación + estado + rango de created_time (watermark).
            $table->index(
                ['conversation_id', 'status', 'created_time'],
                'msgr_msgs_conv_status_created_time_idx'
            );

            // Optimiza condición whereNull(read_at) en la actualización de lecturas por conversación.
            $table->index(
                ['conversation_id', 'message_method', 'read_at', 'created_time'],
                'msgr_msgs_conv_method_read_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropIndex('msgr_msgs_conv_status_created_time_idx');
            $table->dropIndex('msgr_msgs_conv_method_created_time_idx');
            $table->dropIndex('msgr_msgs_conv_method_read_created_idx');
        });
    }
};
