<?php

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
        Schema::table('store_subscription_payments', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->after('subscription_plan_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('duration_days')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_subscription_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn('duration_days');
        });
    }
};
