<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\QrisPayment;
use App\Models\Store;
use App\Models\User;
use App\Services\Contracts\MidtransQrisGatewayContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrisOnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_online_payment_button_only_shows_when_the_store_enabled_it_with_a_server_key(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->assertDontSee('Bayar QRIS Online');

        $store->update(['midtrans_payment_enabled' => true, 'midtrans_server_key' => 'SB-Mid-server-xxx']);

        // The cashier model instance above already lazy-loaded (and cached)
        // its "store" relation before the update - refetch so the next
        // assertion sees the change, same as a real, separate request would.
        Livewire::actingAs($cashier->fresh())
            ->test(Terminal::class)
            ->assertSee('Bayar QRIS Online');
    }

    public function test_starting_an_online_payment_creates_a_pending_charge_and_shows_a_qr(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'midtrans_payment_enabled' => true,
            'midtrans_server_key' => 'SB-Mid-server-xxx',
        ]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 8000,
            'stock_qty' => 10,
        ]);

        $this->app->bind(MidtransQrisGatewayContract::class, fn () => new FakeMidtransQrisGateway);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('payWithQrisOnline')
            ->assertSet('qrisPaymentId', fn ($id) => $id !== null);

        $this->assertDatabaseCount('qris_payments', 1);
        $qrisPayment = QrisPayment::first();
        $this->assertSame('pending', $qrisPayment->status);
        $this->assertSame(18000, $qrisPayment->amount);
        $this->assertSame('https://fake-midtrans.test/qr.png', $qrisPayment->qr_url);
        $this->assertDatabaseCount('transactions', 0);

        // Stock is untouched until the payment actually settles.
        $this->assertSame(10, $product->fresh()->stock_qty);
    }

    public function test_polling_completes_the_sale_once_the_gateway_reports_settlement(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'midtrans_payment_enabled' => true,
            'midtrans_server_key' => 'SB-Mid-server-xxx',
        ]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 8000,
            'stock_qty' => 10,
        ]);

        $fake = new FakeMidtransQrisGateway;
        $this->app->instance(MidtransQrisGatewayContract::class, $fake);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('payWithQrisOnline');

        $fake->nextStatus = 'settlement';

        $component->call('checkQrisPaymentStatus')
            ->assertSet('qrisPaymentId', null);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(9, $product->fresh()->stock_qty);

        $qrisPayment = QrisPayment::first();
        $this->assertSame('settled', $qrisPayment->status);
        $this->assertNotNull($qrisPayment->transaction_id);
    }

    public function test_cancelling_a_pending_payment_lets_the_cashier_retry(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'midtrans_payment_enabled' => true,
            'midtrans_server_key' => 'SB-Mid-server-xxx',
        ]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 8000,
            'stock_qty' => 10,
        ]);

        $this->app->bind(MidtransQrisGatewayContract::class, fn () => new FakeMidtransQrisGateway);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('payWithQrisOnline')
            ->call('cancelQrisPayment')
            ->assertSet('qrisPaymentId', null);

        $this->assertSame('cancelled', QrisPayment::first()->status);
    }
}

class FakeMidtransQrisGateway implements MidtransQrisGatewayContract
{
    public string $nextStatus = 'pending';

    public function charge(Store $store, string $orderId, int $amount): object
    {
        return (object) [
            'transaction_status' => 'pending',
            'actions' => [
                (object) ['name' => 'generate-qr-code', 'url' => 'https://fake-midtrans.test/qr.png'],
            ],
        ];
    }

    public function status(Store $store, string $orderId): object
    {
        return (object) [
            'transaction_status' => $this->nextStatus,
            'fraud_status' => 'accept',
        ];
    }

    public function cancel(Store $store, string $orderId): void
    {
        //
    }
}
