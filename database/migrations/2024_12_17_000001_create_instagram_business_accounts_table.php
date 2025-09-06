<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramBusinessAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_business_accounts', function (Blueprint $table) {
            $table->string('instagram_business_account_id')->primary();
            $table->string('facebook_page_id')->unique();
            $table->string('name')->unique();
            $table->string('access_token');
            $table->json('tasks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_business_accounts');
    }
}
