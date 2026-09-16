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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->text('features')->nullable()->after('description');
        });

        $features = implode("\n", [
            'Kasir & Self-Order tanpa batas transaksi',
            'Produk, kategori, dan tag tanpa batas',
            'Inventory & Stock Opname',
            'Manajemen karyawan & izin akses',
            'Laporan penjualan real-time',
        ]);

        DB::table('subscription_plans')->where('code', 'weekly')->update([
            'description' => 'Cocok untuk mencoba dulu sebelum berlangganan lebih lama.',
            'features' => $features,
        ]);

        DB::table('subscription_plans')->where('code', 'monthly')->update([
            'description' => 'Pilihan paling hemat untuk pemakaian rutin bulanan.',
            'features' => $features,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['description', 'features']);
        });
    }
};
