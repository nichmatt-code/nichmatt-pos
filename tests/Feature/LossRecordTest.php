<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\InventoryItem;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LossRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_loss_deducts_stock_and_ingredients_without_creating_a_sale(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $flour = InventoryItem::create([
            'store_id' => $store->id,
            'name' => 'Tepung',
            'unit' => 'gr',
            'stock_qty' => 1000,
            'min_stock' => 100,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Donat',
            'price' => 8000,
            'cost_price' => 3000,
            'stock_qty' => 10,
        ]);
        $product->ingredients()->attach($flour->id, ['qty_used' => 50]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openLossModal')
            ->call('addLossItem', $product->id)
            ->call('incrementLossQty', $product->id)
            ->set('lossReason', 'Jatuh saat penggorengan')
            ->call('submitLoss')
            ->assertSet('showLossModal', false)
            ->assertDispatched('loss-ready');

        $this->assertDatabaseCount('loss_records', 1);

        $lossRecord = LossRecord::first();
        $this->assertSame('Jatuh saat penggorengan', $lossRecord->reason);
        $this->assertSame(6000, $lossRecord->total_cost_value);
        $this->assertSame(1, $lossRecord->items()->count());
        $this->assertSame(2, $lossRecord->items()->first()->qty);

        $this->assertSame(8, $product->fresh()->stock_qty);
        $this->assertSame(900, $flour->fresh()->stock_qty);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_a_reason_is_required_to_record_a_loss(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Donat',
            'price' => 8000,
            'cost_price' => 3000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openLossModal')
            ->call('addLossItem', $product->id)
            ->set('lossReason', '')
            ->call('submitLoss')
            ->assertHasErrors(['lossReason' => 'required']);

        $this->assertDatabaseCount('loss_records', 0);
    }

    public function test_the_loss_receipt_is_printable(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $lossRecord = LossRecord::create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'loss_no' => 'LOSS-TEST-001',
            'reason' => 'Gelas pecah',
            'total_cost_value' => 5000,
        ]);
        $lossRecord->items()->create([
            'product_name' => 'Es Teh',
            'qty' => 1,
            'cost_price' => 5000,
            'subtotal_cost' => 5000,
        ]);

        $this->actingAs($owner)
            ->get(route('loss-records.receipt', $lossRecord))
            ->assertOk()
            ->assertSee('NOTA KERUGIAN')
            ->assertSee('Gelas pecah')
            ->assertSee('Es Teh');
    }
}
