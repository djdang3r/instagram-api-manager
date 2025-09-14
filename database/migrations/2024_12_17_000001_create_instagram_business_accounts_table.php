<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_business_accounts', function (Blueprint $table) {
            $table->string('instagram_business_account_id')->primary();
            $table->string('facebook_page_id')->nullable(); // Puede ser null inicialmente
            $table->string('name');
            $table->text('access_token'); // TEXT para tokens encriptados
            $table->integer('token_expires_in')->nullable();
            $table->text('permissions')->nullable(); // Campo para almacenar permisos
            $table->json('tasks')->nullable();
            $table->timestamp('token_obtained_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // La clave foránea solo se aplica si hay valor (usando condición)
            $table->foreign('facebook_page_id')
                  ->references('page_id')
                  ->on('facebook_pages')
                  ->onDelete('set null'); // Si se elimina la página, se establece a null

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_business_accounts');
    }
};