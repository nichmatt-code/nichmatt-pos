<?php

namespace Tests\Feature;

use App\Livewire\Customers\Index;
use App\Livewire\Pos\Terminal;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_customer(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCustomer')
            ->set('name', 'Budi')
            ->set('phone', '081234567890')
            ->set('address', 'Jl. Mawar No. 1')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::where('phone', '081234567890')->firstOrFail();

        $this->assertSame($store->id, $customer->store_id);
        $this->assertSame('Budi', $customer->name);
        $this->assertSame('Jl. Mawar No. 1', $customer->address);
    }

    public function test_name_and_phone_are_required(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCustomer')
            ->set('name', '')
            ->set('phone', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'phone' => 'required']);
    }

    public function test_phone_must_be_unique_within_the_same_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCustomer')
            ->set('name', 'Budi Lain')
            ->set('phone', '081234567890')
            ->call('save')
            ->assertHasErrors(['phone' => 'unique']);
    }

    public function test_two_different_stores_can_each_have_a_customer_with_the_same_phone(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);
        Customer::create(['store_id' => $storeA->id, 'name' => 'Budi', 'phone' => '081234567890']);

        Livewire::actingAs($ownerB)
            ->test(Index::class)
            ->call('createCustomer')
            ->set('name', 'Budi')
            ->set('phone', '081234567890')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Customer::withoutGlobalScopes()->where('phone', '081234567890')->count());
    }

    public function test_owner_can_edit_and_delete_a_customer(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editCustomer', $customer->id)
            ->set('name', 'Budi Santoso')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Budi Santoso', $customer->fresh()->name);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('delete', $customer->id);

        $this->assertModelMissing($customer);
    }

    public function test_kasir_without_customers_permission_cannot_reach_the_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/customers')->assertForbidden();
    }

    public function test_cashier_can_pick_an_existing_customer_at_checkout_and_it_links_to_the_transaction(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('customerName', 'Bud')
            ->assertSet('customerMatches', fn ($matches) => $matches->pluck('id')->contains($customer->id))
            ->call('selectCustomer', $customer->id)
            ->assertSet('customerName', 'Budi')
            ->assertSet('selectedCustomerId', $customer->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '18000')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame('Budi', $transaction->customer_name);
    }

    public function test_editing_the_name_after_selecting_a_customer_clears_the_selection(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('selectCustomer', $customer->id)
            ->assertSet('selectedCustomerId', $customer->id)
            ->set('customerName', 'Budi Yang Lain')
            ->assertSet('selectedCustomerId', null);
    }
}
