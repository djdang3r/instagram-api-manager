<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('messenger_contacts', 'local_profile_picture')) {
            Schema::table('messenger_contacts', function (Blueprint $table) {
                $table->string('local_profile_picture')->nullable()->after('profile_picture');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('messenger_contacts', 'local_profile_picture')) {
            Schema::table('messenger_contacts', function (Blueprint $table) {
                $table->dropColumn('local_profile_picture');
            });
        }
    }
};
