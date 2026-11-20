<?php

namespace Tests\Feature;

use App\Mail\AdminLoginAlert;
use App\Models\User;
use App\Services\AdminPushService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password1';

    private string $secret;

    /** @var string[] */
    private array $recoveryCodes;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(AdminPushService::class)->shouldReceive('notifyAdminLogin')->byDefault();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['email' => 'admin@example.com', 'password' => self::PASSWORD, 'is_admin' => true]);
    }

    private function adminWithTwoFactor(): User
    {
        $admin = $this->admin();
        $this->secret = app(TwoFactorService::class)->generateSecret();
        $this->recoveryCodes = app(TwoFactorService::class)->enable($admin, $this->secret);
        // Comme si l'activation datait d'hier : le code courant n'a pas encore servi
        $admin->forceFill(['two_factor_timestep' => null])->save();

        return $admin;
    }

    private function currentCode(): string
    {
        return app(Google2FA::class)->getCurrentOtp($this->secret);
    }

    private function submitPassword(string $email = 'admin@example.com')
    {
        return $this->post('/connexion', ['email' => $email, 'password' => self::PASSWORD]);
    }

    // --- Activation ---

    public function test_admin_can_enable_two_factor(): void
    {
        $admin = $this->admin();

        $page = $this->actingAs($admin)->get(route('admin.security.index'))->assertOk()->assertSee('<svg', false);
        $secret = $page->viewData('secret');

        $this->post(route('admin.security.enable'), [
            'current_password' => self::PASSWORD,
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertRedirect(route('admin.security.index'))->assertSessionHas('recovery_codes', fn ($codes) => count($codes) === 8);

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());
        $this->assertSame($secret, $admin->two_factor_secret);
        // Le secret n'est jamais stocke en clair
        $this->assertStringNotContainsString($secret, DB::table('users')->where('id', $admin->id)->value('two_factor_secret'));
    }

    public function test_enabling_requires_a_valid_code_and_the_password(): void
    {
        $admin = $this->admin();
        $secret = $this->actingAs($admin)->get(route('admin.security.index'))->viewData('secret');

        $this->post(route('admin.security.enable'), ['current_password' => self::PASSWORD, 'code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->post(route('admin.security.enable'), [
            'current_password' => 'Mauvais1',
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertSessionHasErrors('current_password');

        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_security_page_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.security.index'))->assertForbidden();
    }

    public function test_disabling_requires_the_password(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->actingAs($admin)->delete(route('admin.security.disable'), ['current_password' => 'Mauvais1'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $this->delete(route('admin.security.disable'), ['current_password' => self::PASSWORD]);
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $admin = $this->adminWithTwoFactor();
        $old = $admin->two_factor_recovery_codes;

        $this->actingAs($admin)->post(route('admin.security.recovery-codes'), ['current_password' => self::PASSWORD])
            ->assertSessionHas('recovery_codes');

        $this->assertCount(8, $admin->fresh()->two_factor_recovery_codes);
        $this->assertEmpty(array_intersect($old, $admin->fresh()->two_factor_recovery_codes));
    }

    // --- Connexion ---

    public function test_password_alone_does_not_log_in(): void
    {
        $this->adminWithTwoFactor();

        $this->submitPassword()->assertRedirect(route('login.two-factor'));

        $this->assertGuest();
        $this->get('/admin/commandes')->assertRedirect(route('admin.login'));
        Mail::assertNothingSent();
    }

    public function test_valid_code_completes_the_login(): void
    {
        $admin = $this->adminWithTwoFactor();
        $this->get('/admin/commandes');   // destination demandee avant la connexion

        $this->submitPassword();
        $this->get(route('login.two-factor'))->assertOk()->assertSee('90 jours');
        $response = $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode()]);

        $response->assertRedirect('/admin/commandes')->assertCookieMissing(TwoFactorService::TRUST_COOKIE);
        $this->assertAuthenticatedAs($admin);
        Mail::assertSent(AdminLoginAlert::class);
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->adminWithTwoFactor();

        $this->submitPassword();
        $this->post(route('login.two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $this->adminWithTwoFactor();
        $code = $this->currentCode();

        $this->submitPassword();
        $this->post(route('login.two-factor.verify'), ['code' => $code]);
        $this->assertAuthenticated();
        $this->post('/deconnexion');

        $this->submitPassword();
        $this->post(route('login.two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_is_unreachable_without_the_password_step(): void
    {
        $this->adminWithTwoFactor();

        $this->get(route('login.two-factor'))->assertRedirect(route('login'));
        $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode()])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_challenge_expires(): void
    {
        $this->adminWithTwoFactor();

        $this->submitPassword();
        $this->travel(11)->minutes();
        $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode()])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_too_many_wrong_codes_locks_the_challenge(): void
    {
        $this->adminWithTwoFactor();
        $this->submitPassword();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.two-factor.verify'), ['code' => '000000']);
        }

        // Meme le bon code est refuse, et il faut repasser par le mot de passe
        $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode()])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Trop de codes incorrects. Réessayez dans 15 minute(s).']);
        $this->assertGuest();
    }

    public function test_recovery_code_works_once(): void
    {
        $admin = $this->adminWithTwoFactor();
        $code = $this->recoveryCodes[0];

        $this->submitPassword();
        $this->post(route('login.two-factor.verify'), ['code' => strtoupper($code)])
            ->assertSessionHas('warning', 'Code de secours utilisé : il vous en reste 7.');
        $this->assertAuthenticatedAs($admin);
        $this->post('/deconnexion');

        $this->submitPassword();
        $this->post(route('login.two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // --- Appareil memorise 90 jours ---

    private function loginAndTrustDevice(): string
    {
        $this->submitPassword();
        $response = $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode(), 'trust' => '1']);
        $cookie = $response->getCookie(TwoFactorService::TRUST_COOKIE);
        $this->post('/deconnexion');

        $this->assertEqualsWithDelta(now()->addDays(90)->timestamp, $cookie->getExpiresTime(), 60);
        $this->assertTrue($cookie->isHttpOnly());

        return $cookie->getValue();
    }

    public function test_trusted_device_skips_the_code_for_90_days(): void
    {
        $admin = $this->adminWithTwoFactor();
        $cookie = $this->loginAndTrustDevice();

        $this->travel(89)->days();
        $this->withCookie(TwoFactorService::TRUST_COOKIE, $cookie)->submitPassword()->assertRedirect(route('admin.orders.index'));
        $this->assertAuthenticatedAs($admin);
        $this->post('/deconnexion');

        $this->travel(2)->days();
        $this->withCookie(TwoFactorService::TRUST_COOKIE, $cookie)->submitPassword()->assertRedirect(route('login.two-factor'));
        $this->assertGuest();
    }

    public function test_trusting_a_device_keeps_the_session_for_90_days(): void
    {
        $this->adminWithTwoFactor();

        $this->submitPassword();
        $response = $this->post(route('login.two-factor.verify'), ['code' => $this->currentCode(), 'trust' => '1']);

        $remember = collect($response->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($remember);
        $this->assertEqualsWithDelta(now()->addDays(90)->timestamp, $remember->getExpiresTime(), 60);
    }

    public function test_trusted_device_still_needs_the_password(): void
    {
        $this->adminWithTwoFactor();
        $cookie = $this->loginAndTrustDevice();

        $this->withCookie(TwoFactorService::TRUST_COOKIE, $cookie)
            ->post('/connexion', ['email' => 'admin@example.com', 'password' => 'Mauvais1']);

        $this->assertGuest();
    }

    public function test_password_change_forgets_trusted_devices(): void
    {
        $admin = $this->adminWithTwoFactor();
        $cookie = $this->loginAndTrustDevice();

        $admin->update(['password' => 'Nouveau123']);

        $this->withCookie(TwoFactorService::TRUST_COOKIE, $cookie)
            ->post('/connexion', ['email' => 'admin@example.com', 'password' => 'Nouveau123'])
            ->assertRedirect(route('login.two-factor'));
    }

    public function test_trust_cookie_cannot_be_reused_for_another_account(): void
    {
        $this->adminWithTwoFactor();
        $cookie = $this->loginAndTrustDevice();

        $other = User::factory()->create(['email' => 'autre@example.com', 'password' => self::PASSWORD, 'is_admin' => true]);
        app(TwoFactorService::class)->enable($other, app(TwoFactorService::class)->generateSecret());

        $this->withCookie(TwoFactorService::TRUST_COOKIE, $cookie)->submitPassword('autre@example.com')
            ->assertRedirect(route('login.two-factor'));
        $this->assertGuest();
    }

    // --- Secours ---

    public function test_command_disables_two_factor(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->artisan('admin:2fa-disable', ['email' => 'admin@example.com'])->assertSuccessful();
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());

        $this->artisan('admin:2fa-disable', ['email' => 'inconnu@example.com'])->assertFailed();
    }
}
