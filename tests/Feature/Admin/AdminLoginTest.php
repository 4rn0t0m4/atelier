<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\AdminPushService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * L'app installee ne couvre que /admin : connexion, verification et
 * deconnexion doivent s'y derouler sans jamais passer par la boutique.
 */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password1';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(AdminPushService::class)->shouldReceive('notifyAdminLogin')->byDefault();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@example.com', 'password' => self::PASSWORD, 'is_admin' => true]);
    }

    private function submit(string $password = self::PASSWORD, string $email = 'admin@example.com')
    {
        return $this->post(route('admin.login.post'), ['email' => $email, 'password' => $password]);
    }

    public function test_guest_is_sent_to_the_admin_login(): void
    {
        $this->get('/admin/commandes')->assertRedirect('/admin/connexion');
        $this->get('/admin')->assertRedirect('/admin/connexion');

        $this->get('/admin/connexion')
            ->assertOk()
            ->assertSee('admin.webmanifest')
            ->assertSee(route('admin.login.post'));
    }

    public function test_boutique_keeps_its_own_login(): void
    {
        $this->get('/mon-compte')->assertRedirect(route('login'));
    }

    public function test_admin_logs_in_and_lands_in_the_admin(): void
    {
        $admin = $this->admin();

        $this->submit()->assertRedirect(route('admin.orders.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_requested_admin_page_is_restored_after_login(): void
    {
        $this->admin();
        $this->get('/admin/commandes?commande=12');

        $this->submit()->assertRedirect('/admin/commandes?commande=12');
    }

    public function test_a_boutique_page_is_never_restored_from_the_admin_login(): void
    {
        $this->admin();
        $this->get('/mon-compte');   // memorise /mon-compte comme page demandee

        $this->submit()->assertRedirect(route('admin.orders.index'));
    }

    public function test_wrong_password_stays_on_the_admin_login(): void
    {
        $this->admin();

        $this->from('/admin/connexion')->submit('Mauvais1')
            ->assertRedirect('/admin/connexion')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_account_cannot_log_in_from_the_admin(): void
    {
        User::factory()->create(['email' => 'cliente@example.com', 'password' => self::PASSWORD]);

        $this->from('/admin/connexion')->submit(self::PASSWORD, 'cliente@example.com')
            ->assertRedirect('/admin/connexion')
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);

        $this->assertGuest();
    }

    public function test_two_factor_step_stays_in_the_admin(): void
    {
        $admin = $this->admin();
        $secret = app(TwoFactorService::class)->generateSecret();
        app(TwoFactorService::class)->enable($admin, $secret);
        $admin->forceFill(['two_factor_timestep' => null])->save();

        $this->submit()->assertRedirect('/admin/connexion/verification');
        $this->assertGuest();
        $this->get('/admin/connexion/verification')->assertOk()->assertSee(route('admin.login.two-factor.verify'));

        $this->post(route('admin.login.two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('admin.login.two-factor.verify'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_verification_without_password_step_returns_to_the_admin_login(): void
    {
        $this->get('/admin/connexion/verification')->assertRedirect('/admin/connexion');
    }

    public function test_admin_logout_returns_to_the_admin_login(): void
    {
        $this->actingAs($this->admin())->post(route('admin.logout'))->assertRedirect('/admin/connexion');

        $this->assertGuest();
    }

    public function test_logged_in_admin_skips_the_login_page(): void
    {
        $this->actingAs($this->admin())->get('/admin/connexion')->assertRedirect(route('admin.orders.index'));
    }

    public function test_admin_logging_in_from_the_boutique_lands_in_the_admin(): void
    {
        $this->admin();

        $this->post('/connexion', ['email' => 'admin@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.orders.index'));
    }

    public function test_session_closed_elsewhere_returns_to_the_admin_login(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/commandes')->assertOk();

        $admin->update(['password' => 'Nouveau123']);   // mot de passe change depuis un autre appareil

        $this->get('/admin/commandes')->assertRedirect('/admin/connexion');
    }

    public function test_admin_can_change_password_from_the_security_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.security.index'))->assertOk()->assertSee(route('admin.security.password'));

        $this->put(route('admin.security.password'), [
            'current_password' => 'Mauvais1',
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('admin.security.password'), [
            'current_password' => self::PASSWORD,
            'password' => 'Nouveau123',
            'password_confirmation' => 'Nouveau123',
        ])->assertRedirect(route('admin.security.index'))->assertSessionHas('success');

        // L'appareil courant reste connecte
        $this->get('/admin/commandes')->assertOk();
        $this->post(route('admin.logout'));
        $this->submit('Nouveau123')->assertRedirect(route('admin.orders.index'));
    }

    public function test_installed_app_only_covers_the_admin(): void
    {
        $manifest = json_decode(file_get_contents(public_path('admin.webmanifest')), true);

        $this->assertSame('/admin', $manifest['scope']);
        $this->assertStringStartsWith('/admin', $manifest['start_url']);
    }
}
