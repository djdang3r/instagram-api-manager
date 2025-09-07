<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_apps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('app_id')->unique();
            $table->text('app_secret'); // Cambiado a TEXT
            $table->string('verify_token')->unique();
            $table->text('app_access_token'); // Cambiado a TEXT
            $table->json('webhook_fields')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_apps');
    }
};