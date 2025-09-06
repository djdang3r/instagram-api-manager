<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookPagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->string('page_id')->primary();
            $table->ulid('meta_app_id');
            $table->string('name')->nullable();
            $table->string('access_token');
            $table->json('tasks')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('meta_app_id')->references('id')->on('meta_apps')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_pages');
    }
}