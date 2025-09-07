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
            $table->string('facebook_page_id')->nullable();
            $table->string('name')->unique();
            $table->text('access_token');
            $table->integer('token_expires_in')->nullable();
            $table->json('tasks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('facebook_page_id')->references('page_id')->on('facebook_pages')->onDelete('cascade');

            $table->index('access_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_business_accounts');
    }
}
