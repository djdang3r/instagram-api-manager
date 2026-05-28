<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_media_messages', function (Blueprint $table) {
            // Optimiza la búsqueda de duplicados en processAttachments().
            $table->index(
                ['message_id', 'media_type', 'media_url'],
                'msgr_media_msg_type_url_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('messenger_media_messages', function (Blueprint $table) {
            $table->dropIndex('msgr_media_msg_type_url_idx');
        });
    }
};
