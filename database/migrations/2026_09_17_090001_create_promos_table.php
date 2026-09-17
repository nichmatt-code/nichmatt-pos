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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['discount', 'gift']);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Discount fields.
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->unsignedInteger('discount_value')->nullable();

            // Gift fields: buy `min_qty` of product_id, get `gift_qty` of
            // gift_product_id for free.
            $table->unsignedInteger('min_qty')->nullable();
            $table->foreignId('gift_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('gift_qty')->nullable();

            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
