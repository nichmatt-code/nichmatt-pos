<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Landing-page visitors can chat too, without an account: their
     * conversation has no user, and is matched to their browser session
     * by a random token instead.
     */
    public function up(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->index()->after('store_id');
            $table->string('guest_name', 60)->nullable()->after('guest_token');
        });
    }

    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropIndex(['guest_token']);
            $table->dropColumn(['guest_token', 'guest_name']);
        });
    }
};
