<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramContactsTable extends Migration
{
    public function up()
    {
        Schema::create('instagram_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('instagram_account_id');
            $table->string('instagram_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();

            $table->foreign('instagram_account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('instagram_contacts');
    }
}