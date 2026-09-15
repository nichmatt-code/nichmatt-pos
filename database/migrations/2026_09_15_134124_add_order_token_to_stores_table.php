<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('order_token')->nullable()->unique()->after('id');
        });

        DB::table('stores')->whereNull('order_token')->orderBy('id')->pluck('id')->each(function ($id) {
            DB::table('stores')->where('id', $id)->update(['order_token' => Str::random(16)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('order_token');
        });
    }
};
