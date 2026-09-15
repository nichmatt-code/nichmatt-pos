<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $storeOne = Store::create([
            'name' => 'Toko Sembako Jaya',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            'phone' => '081234567890',
        ]);

        User::factory()->create([
            'store_id' => $storeOne->id,
            'role' => 'owner',
            'name' => 'Budi Santoso',
            'email' => 'owner@tokojaya.test',
        ]);

        User::factory()->create([
            'store_id' => $storeOne->id,
            'role' => 'kasir',
            'name' => 'Siti Kasir',
            'email' => 'kasir@tokojaya.test',
        ]);

        $sembako = Category::create(['store_id' => $storeOne->id, 'name' => 'Sembako']);
        $minuman = Category::create(['store_id' => $storeOne->id, 'name' => 'Minuman']);
        $snack = Category::create(['store_id' => $storeOne->id, 'name' => 'Snack']);

        $products = [
            ['category_id' => $sembako->id, 'name' => 'Beras 5kg', 'price' => 65000, 'cost_price' => 58000, 'stock_qty' => 40, 'unit' => 'karung'],
            ['category_id' => $sembako->id, 'name' => 'Minyak Goreng 1L', 'price' => 18000, 'cost_price' => 15500, 'stock_qty' => 60, 'unit' => 'botol'],
            ['category_id' => $sembako->id, 'name' => 'Gula Pasir 1kg', 'price' => 15000, 'cost_price' => 13000, 'stock_qty' => 3, 'unit' => 'kg'],
            ['category_id' => $minuman->id, 'name' => 'Air Mineral 600ml', 'price' => 4000, 'cost_price' => 2800, 'stock_qty' => 120, 'unit' => 'botol'],
            ['category_id' => $minuman->id, 'name' => 'Teh Botol', 'price' => 5000, 'cost_price' => 3800, 'stock_qty' => 80, 'unit' => 'botol'],
            ['category_id' => $snack->id, 'name' => 'Keripik Kentang', 'price' => 9000, 'cost_price' => 6500, 'stock_qty' => 2, 'unit' => 'pcs'],
            ['category_id' => $snack->id, 'name' => 'Biskuit Kaleng', 'price' => 22000, 'cost_price' => 17000, 'stock_qty' => 25, 'unit' => 'kaleng'],
        ];

        foreach ($products as $product) {
            Product::create([...$product, 'store_id' => $storeOne->id]);
        }

        // Second store, used to verify tenant data isolation.
        $storeTwo = Store::create([
            'name' => 'Toko Elektronik Makmur',
            'address' => 'Jl. Sudirman No. 88, Bandung',
        ]);

        User::factory()->create([
            'store_id' => $storeTwo->id,
            'role' => 'owner',
            'name' => 'Andi Wijaya',
            'email' => 'owner@tokomakmur.test',
        ]);

        Product::create([
            'store_id' => $storeTwo->id,
            'name' => 'Kabel HDMI 2m',
            'price' => 45000,
            'cost_price' => 30000,
            'stock_qty' => 15,
            'unit' => 'pcs',
        ]);
    }
}
