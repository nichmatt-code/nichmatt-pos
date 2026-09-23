<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WhatsAppReceiptFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_phone_converts_a_leading_zero_to_the_62_country_code(): void
    {
        $this->assertSame('6281234567890', WhatsAppReceiptFormatter::normalizePhone('081234567890'));
    }

    public function test_normalize_phone_strips_spaces_dashes_and_a_plus_sign(): void
    {
        $this->assertSame('6281234567890', WhatsAppReceiptFormatter::normalizePhone('+62 812-3456-7890'));
    }

    public function test_normalize_phone_leaves_an_already_prefixed_number_alone(): void
    {
        $this->assertSame('6281234567890', WhatsAppReceiptFormatter::normalizePhone('6281234567890'));
    }

    public function test_normalize_phone_returns_null_for_an_empty_or_non_numeric_value(): void
    {
        $this->assertNull(WhatsAppReceiptFormatter::normalizePhone(''));
        $this->assertNull(WhatsAppReceiptFormatter::normalizePhone('   '));
    }

    private function makeTransaction(Store $store, User $cashier, ?string $phone): Transaction
    {
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('customerName', 'Budi')
            ->set('customerPhone', $phone ?? '')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '18000')
            ->call('checkout')
            ->assertHasNoErrors();

        return Transaction::with(['items', 'user', 'store'])->latest('id')->firstOrFail();
    }

    public function test_wa_link_is_null_when_the_transaction_has_no_phone(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $transaction = $this->makeTransaction($store, $cashier, null);

        $this->assertNull($transaction->customer_phone);
        $this->assertNull(WhatsAppReceiptFormatter::waLink($transaction));
    }

    public function test_wa_link_points_to_wame_with_the_normalized_number_and_an_itemized_message(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $transaction = $this->makeTransaction($store, $cashier, '081234567890');

        $this->assertSame('081234567890', $transaction->customer_phone);

        $link = WhatsAppReceiptFormatter::waLink($transaction);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);

        $decoded = urldecode(explode('text=', $link)[1]);
        $this->assertStringContainsString('Budi', $decoded);
        $this->assertStringContainsString($transaction->transaction_no, $decoded);
        $this->assertStringContainsString('Kopi Susu', $decoded);
        $this->assertStringContainsString('18.000', $decoded);
        $this->assertStringContainsString('```', $decoded);
    }

    public function test_selecting_a_registered_customer_fills_in_their_phone_automatically(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Siti', 'phone' => '082211112222']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('selectCustomer', $customer->id)
            ->assertSet('customerPhone', '082211112222')
            ->call('clearSelectedCustomer')
            ->assertSet('customerPhone', '');
    }

    public function test_a_new_transaction_resets_the_phone_field(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('customerPhone', '081234567890')
            ->call('newTransaction')
            ->assertSet('customerPhone', '');
    }

    public function test_the_whatsapp_button_only_shows_up_after_checkout_when_a_phone_was_given(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '18000')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertDontSee('Kirim via WhatsApp');

        $product2 = Product::create([
            'store_id' => $store->id, 'name' => 'Teh Manis', 'price' => 8000, 'cost_price' => 3000, 'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product2->id)
            ->set('customerPhone', '081234567890')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '8000')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSee('Kirim via WhatsApp');
    }

    public function test_the_receipt_pages_show_the_whatsapp_link_only_when_a_phone_is_on_file(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $withPhone = $this->makeTransaction($store, $cashier, '081234567890');
        $withoutPhone = $this->makeTransaction($store, $cashier, null);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $withPhone))
            ->assertOk()
            ->assertSee('Kirim via WhatsApp');

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $withoutPhone))
            ->assertOk()
            ->assertDontSee('Kirim via WhatsApp');

        $store->update(['receipt_format' => 'pdf']);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $withPhone))
            ->assertOk()
            ->assertSee('Kirim via WhatsApp');
    }
}
