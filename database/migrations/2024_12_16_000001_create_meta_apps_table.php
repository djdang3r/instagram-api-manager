<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetaAppsTable extends Migration
{
    public function up(): void
    {
        Schema::create('meta_apps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('app_id')->unique();
            $table->string('app_secret');
            $table->string('verify_token')->unique();
            $table->string('app_access_token');
            $table->json('webhook_fields')->nullable(); // Ej: messages, messaging_postbacks, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_apps');
    }
}