<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_media_messages', function (Blueprint $table) {
            // Guardar URL completa para deduplicar correctamente.
            $table->text('media_url')->nullable()->change();
            $table->string('media_type', 15)->change();
        });

        if (!Schema::hasColumn('messenger_media_messages', 'media_url_hash')) {
            Schema::table('messenger_media_messages', function (Blueprint $table) {
                $table->string('media_url_hash', 64)->nullable()->after('media_url');
            });
        }

        Schema::table('messenger_media_messages', function (Blueprint $table) {
            // Índice compuesto para deduplicación por mensaje + tipo + hash de URL.
            // Evitamos indexar media_url completo para no romper por límites de longitud.
            $table->index(
                ['message_id', 'media_type', 'media_url_hash'],
                'msgr_media_msg_id_type_url_hash_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('messenger_media_messages', function (Blueprint $table) {
            $table->dropIndex('msgr_media_msg_id_type_url_hash_idx');
        });

        if (Schema::hasColumn('messenger_media_messages', 'media_url_hash')) {
            Schema::table('messenger_media_messages', function (Blueprint $table) {
                $table->dropColumn('media_url_hash');
            });
        }

        Schema::table('messenger_media_messages', function (Blueprint $table) {
            // Volver al largo histórico previo.
            $table->string('media_url')->nullable()->change();
            $table->string('media_type')->change();
        });
    }
};
