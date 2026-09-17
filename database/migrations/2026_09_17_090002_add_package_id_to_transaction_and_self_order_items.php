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
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        Schema::table('self_order_items', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });

        Schema::table('self_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });
    }
};
