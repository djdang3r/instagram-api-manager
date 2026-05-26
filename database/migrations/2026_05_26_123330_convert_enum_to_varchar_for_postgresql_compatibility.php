<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte columnas ENUM a VARCHAR para compatibilidad con PostgreSQL.
     * En MySQL, ENUM → VARCHAR. En PostgreSQL, se elimina el CHECK constraint.
     * Esta migración es segura en ambos motores y no pierde datos.
     */
    public function up(): void
    {
        // instagram_messages
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->string('message_method', 20)->default('incoming')->change();
            $table->string('message_type', 50)->default('text')->change();
            $table->string('status', 20)->default('pending')->change();
        });

        // messenger_messages
        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->string('message_method', 20)->default('incoming')->change();
            $table->string('message_type', 50)->default('text')->change();
            $table->string('status', 20)->default('pending')->change();
        });
    }

    /**
     * Revertir a ENUM (solo compatible con MySQL). En PostgreSQL esto fallará
     * porque no soporta ENUM nativo. Se documenta la limitación.
     */
    public function down(): void
    {
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->enum('message_method', ['incoming', 'outgoing'])->default('incoming')->change();
            $table->enum('message_type', [
                'text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply',
                'quick_reply', 'generic_template', 'generic_template_action', 'default_action',
                'postback_interaction', 'postback', 'button_template', 'image', 'document', 'file',
            ])->default('text')->change();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'received'])->default('pending')->change();
        });

        Schema::table('messenger_messages', function (Blueprint $table) {
            $table->enum('message_method', ['incoming', 'outgoing'])->default('incoming')->change();
            $table->enum('message_type', [
                'text', 'audio', 'photo', 'gif', 'video', 'sticker', 'reaction', 'reply',
                'image', 'file', 'quick_reply', 'postback', 'template', 'generic_template',
                'button_template', 'unsupported', 'document',
            ])->default('text')->change();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed', 'received'])->default('pending')->change();
        });
    }
};
