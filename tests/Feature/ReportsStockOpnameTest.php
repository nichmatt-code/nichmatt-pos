<?php

namespace Tests\Feature;

use App\Livewire\Reports\StockOpname as StockOpnameReport;
use App\Models\Store;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsStockOpnameTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_completed_opnames_with_their_difference_summary(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $opname = StockOpname::create([
            'store_id' => $store->id,
            'code' => 'SO-TEST-001',
            'type' => StockOpname::TYPE_PRODUCT,
            'status' => StockOpname::STATUS_COMPLETED,
            'created_by' => $owner->id,
            'completed_by' => $owner->id,
            'completed_at' => now(),
        ]);
        $opname->items()->create([
            'item_name' => 'Nasi Goreng',
            'unit' => 'pcs',
            'system_qty' => 10,
            'counted_qty' => 8,
        ]);

        // A still-running (draft) session must not show up in the report.
        StockOpname::create([
            'store_id' => $store->id,
            'code' => 'SO-DRAFT-001',
            'type' => StockOpname::TYPE_PRODUCT,
            'status' => StockOpname::STATUS_DRAFT,
            'created_by' => $owner->id,
        ]);

        Livewire::actingAs($owner)
            ->test(StockOpnameReport::class)
            ->assertSee('SO-TEST-001')
            ->assertDontSee('SO-DRAFT-001')
            ->assertSee('1 '.__('kurang'));
    }

    public function test_search_filters_by_code(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        StockOpname::create([
            'store_id' => $store->id,
            'code' => 'SO-ALPHA',
            'type' => StockOpname::TYPE_PRODUCT,
            'status' => StockOpname::STATUS_COMPLETED,
            'created_by' => $owner->id,
            'completed_at' => now(),
        ]);
        StockOpname::create([
            'store_id' => $store->id,
            'code' => 'SO-BETA',
            'type' => StockOpname::TYPE_PRODUCT,
            'status' => StockOpname::STATUS_COMPLETED,
            'created_by' => $owner->id,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(StockOpnameReport::class)
            ->set('search', 'ALPHA')
            ->assertSee('SO-ALPHA')
            ->assertDontSee('SO-BETA');
    }
}
