<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_media_messages', function (Blueprint $table) {
            $table->ulid('media_id')->primary();
            $table->string('message_id');
            $table->string('media_type', 15);
            $table->text('url');
            $table->json('json')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('message_id')
                ->references('message_id')
                ->on('instagram_messages')
                ->onDelete('cascade');

            $table->index('media_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_media_messages');
    }
};
