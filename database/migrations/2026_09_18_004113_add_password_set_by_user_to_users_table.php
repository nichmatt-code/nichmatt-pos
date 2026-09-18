<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_set_by_user')->default(true)->after('password');
        });

        // Accounts created via the Google sign-in flow are given a random,
        // never-shown password (see GoogleAuthController) - flag existing
        // ones so their Profile page offers a "set password" form instead
        // of requiring a "current password" they were never given.
        DB::table('users')->whereNotNull('google_id')->update(['password_set_by_user' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_by_user');
        });
    }
};
