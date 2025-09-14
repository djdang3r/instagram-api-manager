<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('state')->unique();
            $table->string('service'); // 'instagram' o 'facebook'
            $table->string('ip_address')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['state', 'service']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_states');
    }
};