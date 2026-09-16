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
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('show_product_images')->default(true)->after('receipt_format');
            $table->boolean('allow_price_edit')->default(false)->after('show_product_images');
            $table->unsignedTinyInteger('tax_percent')->default(0)->after('allow_price_edit');
            $table->unsignedTinyInteger('service_charge_percent')->default(0)->after('tax_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['show_product_images', 'allow_price_edit', 'tax_percent', 'service_charge_percent']);
        });
    }
};
