<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramProfilesTable extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            $table->string('profile_name');
            $table->string('username')->nullable(); 
            $table->string('profile_picture')->nullable();
            $table->string('bio')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instagram_business_account_id')->references('instagram_business_account_id')->on('instagram_business_accounts')->onDelete('cascade');

            $table->index('profile_name');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_profiles');
    }
}