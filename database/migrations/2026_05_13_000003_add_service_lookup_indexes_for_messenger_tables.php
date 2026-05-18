<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_conversations', function (Blueprint $table) {
            // Optimiza búsquedas de conversación activa por página + usuario.
            $table->index(
                ['page_id', 'messenger_user_id', 'deleted_at'],
                'msgr_conv_page_user_deleted_idx'
            );
        });

        Schema::table('messenger_messages', function (Blueprint $table) {
            // Optimiza actualizaciones por rango temporal en una conversación.
            $table->index(
                ['conversation_id', 'message_method', 'created_at'],
                'msgr_msgs_conv_method_created_idx'
            );

            // Optimiza updates por estado dentro de conversación y rango temporal.
            $table->index(
                ['conversation_id', 'status', 'created_at'],
                'msgr_msgs_conv_status_created_idx'
            );

            // Optimiza batch de mensajes pendientes (where status + message_method).
            $table->index(
                ['status', 'message_method'],
                'msgr_msgs_status_method_idx'
            );

            // Optimiza condiciones whereNull(read_at) en updates de lectura.
            $table->index(
                ['conversation_id', 'message_method', 'read_at', 'created_at'],
                'msgr_msgs_conv_method_read_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('messenger_conversations', function (Blueprint $table) {
            $table->dropIndex('msgr_conv_page_user_deleted_idx');
        });

        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->dropIndex('msgr_msgs_conv_method_created_idx');
            $table->dropIndex('msgr_msgs_conv_status_created_idx');
            $table->dropIndex('msgr_msgs_status_method_idx');
            $table->dropIndex('msgr_msgs_conv_method_read_created_idx');
        });
    }
};
