<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads_and_lists_current_features(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Self-Order dengan QR Code')
            ->assertSee('Inventory dengan Resep Otomatis')
            ->assertSee('Data Pelanggan (Member)')
            ->assertSee('Dashboard & Laporan Real-Time')
            ->assertDontSee('Developer');
    }
}
