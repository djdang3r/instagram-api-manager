<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramContactsTable extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            $table->string('instagram_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('profile_picture')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instagram_business_account_id')->references('instagram_business_account_id')->on('instagram_business_accounts')->onDelete('cascade');

            $table->index('instagram_user_id');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_contacts');
    }
}