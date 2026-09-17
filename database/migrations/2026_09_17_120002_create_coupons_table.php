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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code');
            $table->string('name');

            // Discount is entirely optional - a coupon can be gift-only.
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->unsignedInteger('discount_value')->nullable();

            // When true, the discount actually charged is discount_value +
            // (customer's age in years * age_multiplier) at redemption time,
            // rather than a fixed number - e.g. a birthday promo. Requires a
            // customer with a birthdate to be selected at checkout.
            $table->boolean('is_age_based')->default(false);
            $table->integer('age_multiplier')->nullable();

            // Optional free product gift.
            $table->foreignId('gift_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('gift_qty')->nullable();

            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
