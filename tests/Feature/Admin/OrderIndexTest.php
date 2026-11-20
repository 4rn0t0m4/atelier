<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Order::factory()->processing()->create(['number' => 'CMD-ENCOURS']);
        Order::factory()->create(['number' => 'CMD-EXPEDIEE', 'status' => 'shipped']);
    }

    public function test_default_view_lists_orders_to_prepare(): void
    {
        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('CMD-ENCOURS')
            ->assertDontSee('CMD-EXPEDIEE');
    }

    public function test_status_filter(): void
    {
        $this->get(route('admin.orders.index', ['status' => 'shipped']))
            ->assertSee('CMD-EXPEDIEE')
            ->assertDontSee('CMD-ENCOURS');
    }

    public function test_empty_status_lists_every_order(): void
    {
        $this->get('/admin/commandes?status=')
            ->assertSee('CMD-ENCOURS')
            ->assertSee('CMD-EXPEDIEE');
    }

    public function test_search_ignores_the_default_status(): void
    {
        $this->get(route('admin.orders.index', ['search' => 'CMD-EXPEDIEE']))
            ->assertSee('CMD-EXPEDIEE')
            ->assertDontSee('CMD-ENCOURS');
    }
}
