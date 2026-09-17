<?php

namespace Tests\Feature;

use App\Livewire\Reports\LossHistory;
use App\Models\LossRecord;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LossHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_loss_records_with_their_items_and_a_receipt_link(): void
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

        Livewire::actingAs($owner)
            ->test(LossHistory::class)
            ->assertSee('LOSS-1')
            ->assertSee('Gelas pecah')
            ->assertSee('Es Teh')
            ->assertSeeHtml(route('loss-records.receipt', $record));
    }

    public function test_search_filters_by_reason(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-1', 'reason' => 'Gelas pecah', 'total_cost_value' => 5000]);
        LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-2', 'reason' => 'Ayam gosong', 'total_cost_value' => 8000]);

        Livewire::actingAs($owner)
            ->test(LossHistory::class)
            ->set('search', 'Gelas')
            ->assertSee('LOSS-1')
            ->assertDontSee('LOSS-2');
    }

    public function test_total_value_reflects_only_the_selected_date_range(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $inRange = LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-1', 'reason' => 'A', 'total_cost_value' => 5000]);
        $outOfRange = LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-2', 'reason' => 'B', 'total_cost_value' => 8000]);
        $outOfRange->forceFill(['created_at' => now()->subMonths(2)])->save();

        $totalValue = Livewire::actingAs($owner)->test(LossHistory::class)->viewData('totalValue');

        $this->assertSame(5000, $totalValue);
    }
}
