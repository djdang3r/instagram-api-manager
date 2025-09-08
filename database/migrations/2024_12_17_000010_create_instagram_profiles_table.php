<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('instagram_business_account_id');
            
            // Información básica del perfil
            $table->string('profile_name');
            $table->string('user_id');
            $table->string('username')->nullable();
            $table->text('profile_picture')->nullable();
            $table->text('bio')->nullable();
            
            // Nuevos campos según la documentación de la API
            $table->string('account_type')->nullable()->comment('Business o Media_Creator');
            $table->unsignedBigInteger('followers_count')->nullable();
            $table->unsignedBigInteger('follows_count')->nullable();
            $table->unsignedBigInteger('media_count')->nullable();
            
            // Campos adicionales que podrían ser útiles
            $table->string('website')->nullable();
            $table->string('category_name')->nullable();
            $table->boolean('is_verified')->default(false);
            
            // Metadata
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_api_response')->nullable()->comment('Respuesta completa de la API para referencia');

            $table->timestamps();
            $table->softDeletes();

            // Clave foránea
            $table->foreign('instagram_business_account_id')
                  ->references('instagram_business_account_id')
                  ->on('instagram_business_accounts')
                  ->onDelete('cascade');

            // Índices
            $table->index('profile_name');
            $table->index('username');
            $table->index('account_type');
            $table->index('is_verified');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_profiles');
    }
};