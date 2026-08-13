<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna instagram_business_account a facebook_pages.
     * El FacebookAccountService::handleCallback() guarda el IG Business Account
     * vinculado a cada pagina (me/accounts?fields=instagram_business_account),
     * pero la columna no existia en la migracion original.
     */
    public function up(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->string('instagram_business_account')->nullable()->after('access_token');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_pages', function (Blueprint $table) {
            $table->dropColumn('instagram_business_account');
        });
    }
};
