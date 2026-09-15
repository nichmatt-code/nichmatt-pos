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
        // Empty strings (as opposed to NULL) would collide under a unique
        // index, so normalize blank values first.
        DB::table('products')->where('sku', '')->update(['sku' => null]);
        DB::table('products')->where('barcode', '')->update(['barcode' => null]);

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['store_id', 'sku']);
            $table->unique(['store_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'sku']);
            $table->dropUnique(['store_id', 'barcode']);
        });
    }
};
