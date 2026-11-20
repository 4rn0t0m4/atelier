<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Mail\AdminLoginAlert;
use App\Models\User;
use App\Services\AdminPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1';

    // --- Alerte de connexion admin ---

    public function test_admin_login_triggers_an_alert(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['email' => 'admin@example.com', 'password' => 'Password1', 'is_admin' => true]);

        $this->mock(AdminPushService::class)
            ->shouldReceive('notifyAdminLogin')
            ->once()
            ->withArgs(fn ($user, $device, $ip) => $user->is($admin) && $device === 'Safari sur iPhone' && $ip === '127.0.0.1');

        $this->withHeader('User-Agent', self::IPHONE)
            ->post('/connexion', ['email' => 'admin@example.com', 'password' => 'Password1']);

        $this->assertAuthenticatedAs($admin);
        Mail::assertSent(AdminLoginAlert::class, fn ($mail) => $mail->hasTo('admin@example.com') && $mail->device === 'Safari sur iPhone');
    }

    public function test_customer_login_triggers_no_alert(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'cliente@example.com', 'password' => 'Password1']);
        $this->mock(AdminPushService::class)->shouldNotReceive('notifyAdminLogin');

        $this->post('/connexion', ['email' => 'cliente@example.com', 'password' => 'Password1']);

        $this->assertAuthenticated();
        Mail::assertNothingSent();
    }

    public function test_failed_admin_login_triggers_no_alert(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'Password1', 'is_admin' => true]);
        $this->mock(AdminPushService::class)->shouldNotReceive('notifyAdminLogin');

        $this->post('/connexion', ['email' => 'admin@example.com', 'password' => 'Mauvais1']);

        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_device_description(): void
    {
        $this->assertSame('Safari sur iPhone', AuthController::describeDevice(self::IPHONE));
        $this->assertSame('Chrome sur Android', AuthController::describeDevice('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36'));
        $this->assertSame('Edge sur Windows', AuthController::describeDevice('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0'));
        $this->assertSame('Appareil inconnu', AuthController::describeDevice('curl/8.4'));
    }

    // --- Blocage apres echecs repetes ---

    public function test_account_is_locked_after_repeated_failures(): void
    {
        // Le throttle de route (5/min par IP) masquerait le blocage par compte
        $this->withoutMiddleware(ThrottleRequests::class);
        User::factory()->create(['email' => 'jean@example.com', 'password' => 'Password1']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Mauvais1']);
        }

        // Meme avec le bon mot de passe, le compte reste bloque...
        $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Password1'])
            ->assertSessionHasErrors(['email' => 'Trop de tentatives. Réessayez dans 15 minute(s).']);
        $this->assertGuest();

        // ...un autre compte n'est pas touche...
        User::factory()->create(['email' => 'marie@example.com', 'password' => 'Password1']);
        $this->post('/connexion', ['email' => 'marie@example.com', 'password' => 'Password1']);
        $this->assertAuthenticated();
        $this->post('/deconnexion');

        // ...et le blocage se leve au bout de 15 minutes
        $this->travel(16)->minutes();
        $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Password1']);
        $this->assertAuthenticated();
    }

    public function test_successful_login_resets_the_failure_count(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        User::factory()->create(['email' => 'jean@example.com', 'password' => 'Password1']);

        foreach (range(1, 4) as $attempt) {
            $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Mauvais1']);
        }
        $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Password1']);
        $this->post('/deconnexion');

        $this->post('/connexion', ['email' => 'jean@example.com', 'password' => 'Mauvais1'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
    }

    // --- Changement de mot de passe ---

    public function test_password_change_elsewhere_logs_this_device_out(): void
    {
        $user = User::factory()->create(['password' => 'Password1']);

        $this->actingAs($user)->get('/mon-compte')->assertOk();

        // Mot de passe change depuis un autre appareil
        $user->update(['password' => 'Nouveau123']);

        $this->get('/mon-compte')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_changing_password_keeps_the_current_device_logged_in(): void
    {
        $user = User::factory()->create(['password' => 'Password1']);

        $this->actingAs($user)->get('/mon-compte')->assertOk();
        $this->patch('/mon-compte/mot-de-passe', [
            'current_password' => 'Password1',
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertSessionHas('success');

        $this->get('/mon-compte')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    // --- En-tetes ---

    public function test_security_headers_are_sent_everywhere(): void
    {
        $this->get('/connexion')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/connexion')
            ->assertHeader('Strict-Transport-Security', 'max-age=15552000');
    }

    public function test_admin_pages_are_never_cached_nor_indexed(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/commandes')->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertStringNotContainsString('no-store', (string) $this->get('/boutique')->headers->get('Cache-Control'));
    }
}
