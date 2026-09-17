<?php

namespace Tests\Feature;

use App\Livewire\Branch\Settings;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(Store $store, User $cashier): Transaction
    {
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
            'transaction_no' => 'TRX-'.uniqid(),
            'customer_name' => 'Budi',
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

        return $transaction;
    }

    public function test_default_receipt_uses_the_thermal_layout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $transaction = $this->makeTransaction($store, $cashier);

        $this->assertSame('thermal', $store->fresh()->receipt_format);

        $response = $this->actingAs($cashier)->get(route('transactions.receipt', $transaction));

        $response->assertOk();
        $response->assertViewIs('transactions.receipt');
        $response->assertSee('Nasi Goreng');
        $response->assertSee($transaction->transaction_no);
    }

    public function test_store_can_switch_to_the_pdf_invoice_layout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'receipt_format' => 'pdf']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $transaction = $this->makeTransaction($store, $cashier);

        $response = $this->actingAs($cashier)->get(route('transactions.receipt', $transaction));

        $response->assertOk();
        $response->assertViewIs('transactions.receipt-pdf');
        $response->assertSee('Invoice');
        $response->assertSee('Nasi Goreng');
    }

    public function test_receipt_uses_the_stores_configured_paper_width(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'receipt_width' => '58mm']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $transaction = $this->makeTransaction($store, $cashier);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertSee('width: 58mm', false);
    }

    public function test_receipt_shows_a_custom_footer_message_when_set(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'receipt_footer_text' => 'Sampai jumpa lagi!']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $transaction = $this->makeTransaction($store, $cashier);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertSee('Sampai jumpa lagi!')
            ->assertDontSee('Terima kasih telah berbelanja');
    }

    public function test_a_cashier_cannot_open_another_stores_receipt(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashierOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'kasir']);
        $cashierTwo = User::factory()->create(['store_id' => $storeTwo->id, 'role' => 'kasir']);
        $transaction = $this->makeTransaction($storeTwo, $cashierTwo);

        $this->actingAs($cashierOne)->get(route('transactions.receipt', $transaction))->assertNotFound();
    }

    public function test_owner_can_change_the_receipt_format_from_branch_settings(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('receiptFormat', 'pdf')
            ->call('saveReceiptFormat')
            ->assertHasNoErrors();

        $this->assertSame('pdf', $store->fresh()->receipt_format);
    }

    public function test_receipt_format_only_accepts_known_values(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('receiptFormat', 'fax-machine')
            ->call('saveReceiptFormat')
            ->assertHasErrors('receiptFormat');

        $this->assertSame('thermal', $store->fresh()->receipt_format);
    }

    public function test_sales_report_lists_a_print_receipt_action_per_transaction(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $transaction = $this->makeTransaction($store, $owner);

        $this->actingAs($owner)
            ->get('/reports/sales')
            ->assertOk()
            ->assertSee('Cetak Struk')
            ->assertSee(route('transactions.receipt', $transaction), false);
    }
}
