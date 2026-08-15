<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los captions de Instagram pueden superar los 255 chars de varchar.
     * Al sincronizar posts (syncPosts) un caption largo causaba
     * 'value too long for type character varying(255)' y el post no se guardaba.
     */
    public function up(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->text('caption')->nullable()->change();
            // Las URLs de CDN de Instagram superan 255 chars.
            $table->text('media_url')->nullable()->change();
            $table->text('permalink')->nullable()->change();
            $table->text('thumbnail_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->string('caption', 255)->nullable()->change();
            $table->string('media_url', 255)->nullable()->change();
            $table->string('permalink', 255)->nullable()->change();
            $table->string('thumbnail_url', 255)->nullable()->change();
        });
    }
};
