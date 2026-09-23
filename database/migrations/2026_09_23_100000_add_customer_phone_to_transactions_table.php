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
        Schema::table('transactions', function (Blueprint $table) {
            // A phone number captured at checkout time so the receipt can be
            // sent over WhatsApp - either copied from a selected Customer or
            // typed in free-hand for a walk-in, same relationship
            // `customer_name` already has to `customer_id`.
            $table->string('customer_phone', 30)->nullable()->after('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
