<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    // --- Accès protégé ---

    public function test_guest_cannot_access_account(): void
    {
        $this->get('/mon-compte')->assertRedirect(route('login'));
        $this->get('/mon-compte/commandes')->assertRedirect(route('login'));
        $this->get('/mon-compte/profil')->assertRedirect(route('login'));
        $this->get('/mon-compte/coordonnees')->assertRedirect(route('login'));
    }

    // --- Dashboard ---

    public function test_account_dashboard_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mon-compte')
            ->assertStatus(200);
    }

    // --- Commandes ---

    public function test_orders_page_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mon-compte/commandes')
            ->assertStatus(200);
    }

    public function test_user_can_view_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get("/mon-compte/commandes/{$order->id}")
            ->assertStatus(200)
            ->assertSee($order->number);
    }

    public function test_user_cannot_view_other_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)
            ->get("/mon-compte/commandes/{$order->id}")
            ->assertStatus(403);
    }

    // --- Profil ---

    public function test_profile_page_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mon-compte/profil')
            ->assertStatus(200);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/mon-compte/profil', [
                'name' => 'Nouveau Nom',
                'email' => $user->email,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
        ]);
    }

    // --- Mot de passe ---

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword1']);

        $this->actingAs($user)
            ->patch('/mon-compte/mot-de-passe', [
                'current_password' => 'OldPassword1',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // --- Adresse ---

    public function test_address_page_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mon-compte/coordonnees')
            ->assertStatus(200);
    }

    public function test_user_can_update_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/mon-compte/coordonnees', [
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'address_1' => '5 avenue des Champs',
                'city' => 'Lyon',
                'postcode' => '69001',
                'country' => 'FR',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Marie',
            'city' => 'Lyon',
        ]);
    }
}
