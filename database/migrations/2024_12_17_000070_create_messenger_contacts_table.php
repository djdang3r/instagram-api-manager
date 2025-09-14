<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('page_id');
            $table->string('messenger_user_id');
            $table->string('username')->nullable();
            $table->text('profile_picture')->nullable(); // Cambiado a TEXT

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');

            $table->unique('messenger_user_id');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_contacts');
    }
};