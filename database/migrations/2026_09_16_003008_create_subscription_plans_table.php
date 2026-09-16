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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('duration_days');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('promo_price')->nullable();
            $table->string('promo_label')->nullable();
            $table->timestamp('promo_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the two plans this platform launches with, replacing the old
        // hardcoded Store::SUBSCRIPTION_MONTHLY_PRICE constant.
        DB::table('subscription_plans')->insert([
            [
                'code' => 'weekly',
                'name' => 'Langganan Mingguan',
                'duration_days' => 7,
                'price' => 35_000,
                'promo_price' => null,
                'promo_label' => null,
                'promo_ends_at' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'monthly',
                'name' => 'Langganan Bulanan',
                'duration_days' => 30,
                'price' => 100_000,
                'promo_price' => null,
                'promo_label' => null,
                'promo_ends_at' => null,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
