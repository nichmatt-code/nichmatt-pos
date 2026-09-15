<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_developer')->default(false)->after('role');
        });

        // Bootstrap: the platform owner's account always has developer
        // access, regardless of which store it belongs to.
        User::where('email', User::BOOTSTRAP_DEVELOPER_EMAIL)->update(['is_developer' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_developer');
        });
    }
};
