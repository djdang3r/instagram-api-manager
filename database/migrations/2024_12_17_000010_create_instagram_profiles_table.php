<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('instagram_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('instagram_account_id');
            $table->string('profile_name');
            $table->string('profile_picture')->nullable();
            $table->string('bio')->nullable();
            $table->timestamps();

            $table->foreign('instagram_account_id')->references('id')->on('instagram_accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('instagram_profiles');
    }
}