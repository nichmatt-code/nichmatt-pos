<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('store:seed-demo-data {email : Email of the store owner} {--force : Skip the confirmation prompt}')]
#[Description('Replace one store\'s categories, tags, products, inventory, and sample transactions with a realistic restaurant demo dataset')]
class SeedStoreDemoData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $owner = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $owner || ! $owner->store_id) {
            $this->error("Tidak ada akun dengan email \"{$email}\" yang punya toko.");

            return self::FAILURE;
        }

        $store = Store::withoutGlobalScopes()->findOrFail($owner->store_id);

        $counts = [
            ['Kategori', Category::withoutGlobalScopes()->where('store_id', $store->id)->count()],
            ['Tag', Tag::withoutGlobalScopes()->where('store_id', $store->id)->count()],
            ['Produk', Product::withoutGlobalScopes()->where('store_id', $store->id)->count()],
            ['Inventory', InventoryItem::withoutGlobalScopes()->where('store_id', $store->id)->count()],
            ['Transaksi', Transaction::withoutGlobalScopes()->where('store_id', $store->id)->count()],
        ];

        $this->info("Toko: {$store->name} (ID {$store->id}, pemilik: {$owner->email})");
        $this->table(['Data', 'Jumlah saat ini'], $counts);

        $this->warn('Perintah ini akan MENGHAPUS semua kategori, tag, produk, inventory, self-order, stock opname, dan transaksi milik toko ini (karyawan TIDAK disentuh), lalu menggantinya dengan data demo resto.');

        if (! $this->option('force') && ! $this->confirm('Lanjutkan?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($store) {
            $this->wipe($store);

            $categories = $this->seedCategories($store);
            $tags = $this->seedTags($store);
            $products = $this->seedProducts($store, $categories, $tags);
            $this->seedInventory($store);
            $this->seedTransactions($store, $products);
        });

        $this->info('Selesai! Data demo resto berhasil diisi.');

        return self::SUCCESS;
    }

    private function wipe(Store $store): void
    {
        $storeId = $store->id;

        // Deleting in this order lets each table's cascadeOnDelete/nullOnDelete
        // foreign keys clean up their own children (items, movements, pivots)
        // without needing to touch those tables directly.
        Transaction::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        SelfOrder::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        StockOpname::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        Product::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        InventoryItem::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        Category::withoutGlobalScopes()->where('store_id', $storeId)->delete();
        Tag::withoutGlobalScopes()->where('store_id', $storeId)->delete();
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(Store $store): array
    {
        return collect(['Makanan Utama', 'Minuman', 'Snack & Gorengan', 'Dessert'])
            ->mapWithKeys(fn (string $name) => [$name => Category::create(['store_id' => $store->id, 'name' => $name])])
            ->all();
    }

    /**
     * @return array<string, Tag>
     */
    private function seedTags(Store $store): array
    {
        return collect(['Pedas', 'Best Seller', 'Vegetarian', 'Promo'])
            ->mapWithKeys(fn (string $name) => [$name => Tag::create(['store_id' => $store->id, 'name' => $name])])
            ->all();
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Tag>  $tags
     * @return Collection<int, Product>
     */
    private function seedProducts(Store $store, array $categories, array $tags): Collection
    {
        $definitions = [
            ['name' => 'Nasi Goreng Spesial', 'category' => 'Makanan Utama', 'price' => 25000, 'cost_price' => 15000, 'stock_qty' => 40, 'unit' => 'porsi', 'tags' => ['Best Seller']],
            ['name' => 'Ayam Geprek Sambal Matah', 'category' => 'Makanan Utama', 'price' => 22000, 'cost_price' => 13000, 'stock_qty' => 30, 'unit' => 'porsi', 'tags' => ['Pedas', 'Best Seller']],
            ['name' => 'Mie Ayam Bakso', 'category' => 'Makanan Utama', 'price' => 20000, 'cost_price' => 11000, 'stock_qty' => 35, 'unit' => 'porsi'],
            ['name' => 'Soto Ayam', 'category' => 'Makanan Utama', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 25, 'unit' => 'porsi'],
            ['name' => 'Nasi Rendang', 'category' => 'Makanan Utama', 'price' => 28000, 'cost_price' => 17000, 'stock_qty' => 20, 'unit' => 'porsi', 'tags' => ['Pedas']],
            ['name' => 'Gado-Gado', 'category' => 'Makanan Utama', 'price' => 17000, 'cost_price' => 9000, 'stock_qty' => 20, 'unit' => 'porsi', 'tags' => ['Vegetarian']],

            ['name' => 'Es Teh Manis', 'category' => 'Minuman', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 100, 'unit' => 'gelas'],
            ['name' => 'Es Jeruk', 'category' => 'Minuman', 'price' => 7000, 'cost_price' => 3000, 'stock_qty' => 80, 'unit' => 'gelas'],
            ['name' => 'Kopi Hitam', 'category' => 'Minuman', 'price' => 8000, 'cost_price' => 3500, 'stock_qty' => 60, 'unit' => 'gelas'],
            ['name' => 'Air Mineral', 'category' => 'Minuman', 'price' => 4000, 'cost_price' => 2000, 'stock_qty' => 0, 'unit' => 'botol', 'is_unlimited_stock' => true],
            ['name' => 'Jus Alpukat', 'category' => 'Minuman', 'price' => 12000, 'cost_price' => 6000, 'stock_qty' => 4, 'unit' => 'gelas'],

            ['name' => 'Tahu Isi', 'category' => 'Snack & Gorengan', 'price' => 8000, 'cost_price' => 4000, 'stock_qty' => 40, 'unit' => 'porsi', 'tags' => ['Vegetarian']],
            ['name' => 'Pisang Goreng', 'category' => 'Snack & Gorengan', 'price' => 10000, 'cost_price' => 5000, 'stock_qty' => 30, 'unit' => 'porsi'],
            ['name' => 'Kentang Goreng', 'category' => 'Snack & Gorengan', 'price' => 15000, 'cost_price' => 8000, 'stock_qty' => 0, 'unit' => 'porsi', 'is_out_of_stock' => true],

            ['name' => 'Es Krim Goreng', 'category' => 'Dessert', 'price' => 15000, 'cost_price' => 8000, 'stock_qty' => 15, 'unit' => 'porsi', 'tags' => ['Best Seller', 'Promo']],
            ['name' => 'Puding Coklat', 'category' => 'Dessert', 'price' => 10000, 'cost_price' => 5000, 'stock_qty' => 20, 'unit' => 'porsi'],
        ];

        return collect($definitions)->map(function (array $definition) use ($store, $categories, $tags) {
            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $categories[$definition['category']]->id,
                'name' => $definition['name'],
                'sku' => Product::generateUniqueSku($store->id),
                'barcode' => Product::generateUniqueBarcode($store->id),
                'price' => $definition['price'],
                'cost_price' => $definition['cost_price'],
                'stock_qty' => $definition['stock_qty'],
                'unit' => $definition['unit'],
                'is_unlimited_stock' => $definition['is_unlimited_stock'] ?? false,
                'is_out_of_stock' => $definition['is_out_of_stock'] ?? false,
            ]);

            $tagIds = collect($definition['tags'] ?? [])->map(fn (string $name) => $tags[$name]->id);
            $product->tags()->sync($tagIds);

            return $product;
        });
    }

    private function seedInventory(Store $store): void
    {
        $items = [
            ['name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 50, 'min_stock' => 10],
            ['name' => 'Ayam Potong', 'unit' => 'kg', 'stock_qty' => 20, 'min_stock' => 5],
            ['name' => 'Minyak Goreng', 'unit' => 'liter', 'stock_qty' => 15, 'min_stock' => 5],
            ['name' => 'Bawang Merah', 'unit' => 'kg', 'stock_qty' => 8, 'min_stock' => 3],
            ['name' => 'Bawang Putih', 'unit' => 'kg', 'stock_qty' => 3, 'min_stock' => 5],
            ['name' => 'Gula Pasir', 'unit' => 'kg', 'stock_qty' => 25, 'min_stock' => 5],
            ['name' => 'Es Batu', 'unit' => 'kg', 'stock_qty' => 40, 'min_stock' => 10],
            ['name' => 'Tepung Terigu', 'unit' => 'kg', 'stock_qty' => 12, 'min_stock' => 5],
        ];

        foreach ($items as $item) {
            InventoryItem::create([
                'store_id' => $store->id,
                'name' => $item['name'],
                'sku' => InventoryItem::generateUniqueSku($store->id),
                'unit' => $item['unit'],
                'stock_qty' => $item['stock_qty'],
                'min_stock' => $item['min_stock'],
            ]);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function seedTransactions(Store $store, Collection $products): void
    {
        $cashier = User::withoutGlobalScopes()->where('store_id', $store->id)->first();
        $paymentMethods = ['cash', 'qris', 'kartu'];
        $sellable = $products->reject(fn (Product $product) => $product->is_out_of_stock);

        for ($day = 13; $day >= 0; $day--) {
            $transactionsToday = random_int(2, 5);

            for ($i = 0; $i < $transactionsToday; $i++) {
                $itemCount = random_int(1, 4);
                $items = $sellable->random(min($itemCount, $sellable->count()));
                $subtotal = 0;

                $createdAt = now()->subDays($day)->setTime(random_int(10, 21), random_int(0, 59));
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                $transaction = Transaction::create([
                    'store_id' => $store->id,
                    'user_id' => $cashier->id,
                    'transaction_no' => 'TRX-'.$createdAt->format('Ymd-His').'-'.random_int(100, 999),
                    'subtotal' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'payment_method' => $paymentMethod,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'status' => 'completed',
                    'prepared_at' => $createdAt,
                ]);

                foreach ($items as $product) {
                    $qty = random_int(1, 3);
                    $lineTotal = $product->price * $qty;
                    $subtotal += $lineTotal;

                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $product->price,
                        'cost_price' => $product->cost_price,
                        'qty' => $qty,
                        'subtotal' => $lineTotal,
                    ]);
                }

                $cashRounding = collect([0, 0, 5000, 10000])->random();
                $paidAmount = $paymentMethod === 'cash' ? $subtotal + $cashRounding : $subtotal;

                $transaction->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'paid_amount' => $paidAmount,
                    'change_amount' => max(0, $paidAmount - $subtotal),
                ]);

                $transaction->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();
            }
        }
    }
}
