<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            // Optimiza lookup por user_id en findBusinessAccount().
            $table->index('user_id', 'ig_profiles_user_id_idx');
        });

        Schema::table('instagram_conversations', function (Blueprint $table) {
            // Optimiza findOrCreateConversation() por cuenta + usuario activo (deleted_at null).
            $table->index(
                ['instagram_business_account_id', 'instagram_user_id', 'deleted_at'],
                'ig_conv_business_user_deleted_idx'
            );
        });

        Schema::table('instagram_messages', function (Blueprint $table) {
            // Optimiza updates de read por rango temporal dentro de una conversación.
            $table->index(
                ['conversation_id', 'message_method', 'created_time'],
                'ig_msgs_conv_method_created_idx'
            );

            // Optimiza updates por watermark (conversation + status + created_time).
            $table->index(
                ['conversation_id', 'status', 'created_time'],
                'ig_msgs_conv_created_status_idx'
            );

            // Optimiza batch de mensajes pendientes (where status + message_method).
            $table->index(
                ['status', 'message_method'],
                'ig_msgs_status_method_idx'
            );

            // Optimiza condición whereNull(read_at) en la actualización de lecturas por conversación.
            $table->index(
                ['conversation_id', 'message_method', 'read_at', 'created_time'],
                'ig_msgs_conv_method_read_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->dropIndex('ig_profiles_user_id_idx');
        });

        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->dropIndex('ig_conv_business_user_deleted_idx');
        });

        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->dropIndex('ig_msgs_conv_method_created_idx');
            $table->dropIndex('ig_msgs_conv_created_status_idx');
            $table->dropIndex('ig_msgs_status_method_idx');
            $table->dropIndex('ig_msgs_conv_method_read_created_idx');
        });
    }
};
