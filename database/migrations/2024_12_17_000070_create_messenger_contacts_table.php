<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessengerContactsTable extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('page_id');
            $table->string('messenger_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();

            $table->foreign('page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_contacts');
    }
}