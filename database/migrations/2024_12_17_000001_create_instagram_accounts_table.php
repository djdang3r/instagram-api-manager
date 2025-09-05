<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramAccountsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('username')->unique();
            $table->string('instagram_user_id')->unique();
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_accounts');
    }
}
