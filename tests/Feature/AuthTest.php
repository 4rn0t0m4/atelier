<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // --- Inscription ---

    public function test_register_page_loads(): void
    {
        $this->get('/inscription')->assertStatus(200);
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/inscription', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'jean@example.com']);
    }

    public function test_register_validation_errors(): void
    {
        $this->post('/inscription', [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jean@example.com']);

        $this->post('/inscription', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertSessionHasErrors(['email']);
    }

    // --- Connexion ---

    public function test_login_page_loads(): void
    {
        $this->get('/connexion')->assertStatus(200);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'jean@example.com',
            'password' => 'Password1',
        ]);

        $response = $this->post('/connexion', [
            'email' => 'jean@example.com',
            'password' => 'Password1',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'jean@example.com',
            'password' => 'Password1',
        ]);

        $this->post('/connexion', [
            'email' => 'jean@example.com',
            'password' => 'WrongPass1',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    // --- Déconnexion ---

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/deconnexion')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    // --- Guest middleware ---

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/connexion')
            ->assertRedirect();
    }

    public function test_authenticated_user_cannot_access_register_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/inscription')
            ->assertRedirect();
    }
}
