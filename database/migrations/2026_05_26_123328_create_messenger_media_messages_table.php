<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_media_messages', function (Blueprint $table) {
            $table->ulid('media_id')->primary();
            $table->string('message_id');
            $table->string('media_type');
            $table->string('media_url')->nullable();
            $table->string('local_path')->nullable();
            $table->json('json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('message_id')->references('message_id')->on('messenger_messages')->onDelete('cascade');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_media_messages');
    }
};
