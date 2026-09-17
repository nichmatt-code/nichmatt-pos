<?php

namespace Tests\Feature;

use App\Models\LossRecord;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\ThermalReceiptFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalReceiptFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_lines_use_32_columns_for_a_58mm_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'receipt_width' => '58mm']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);
        $transaction = Transaction::create([
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'transaction_no' => 'TRX-1',
            'subtotal' => 20000,
            'discount' => 0,
            'total' => 20000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 20000,
            'cost_price' => 12000,
            'qty' => 1,
            'subtotal' => 20000,
        ]);

        $lines = ThermalReceiptFormatter::forTransaction($transaction->fresh());

        $this->assertContains('TRX-1', $lines);
        $this->assertContains('Nasi Goreng', $lines);
        $this->assertContains(str_repeat('-', 32), $lines);
        $this->assertTrue(collect($lines)->contains(fn ($line) => str_contains($line, '20.000')));
    }

    public function test_bill_lines_include_the_coupon_discount_when_present(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'receipt_width' => '80mm']);

        $bill = [
            'items' => [
                ['name' => 'Es Teh', 'qty' => 2, 'price' => 5000, 'note' => null],
            ],
            'subtotal' => 10000,
            'discount' => 0,
            'coupon_discount_amount' => 2000,
            'total' => 8000,
            'customer_name' => null,
            'note' => null,
        ];

        $lines = ThermalReceiptFormatter::forBill($store, $bill);

        $this->assertTrue(collect($lines)->contains(fn ($line) => str_contains($line, 'Diskon Kupon')));
    }

    public function test_loss_record_lines_include_the_total_value(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $record = LossRecord::create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'loss_no' => 'LOSS-1',
            'reason' => 'Gelas pecah',
            'total_cost_value' => 5000,
        ]);
        $record->items()->create(['product_name' => 'Es Teh', 'qty' => 1, 'cost_price' => 5000, 'subtotal_cost' => 5000]);

        $lines = ThermalReceiptFormatter::forLossRecord($record->fresh());

        $this->assertContains('LOSS-1', $lines);
        $this->assertTrue(collect($lines)->contains(fn ($line) => str_contains($line, '5.000')));
    }
}
