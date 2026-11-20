<?php

namespace App\Http\Controllers;

use App\Mail\AdminLoginAlert;
use App\Models\User;
use App\Services\AdminPushService;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Echecs de connexion toleres pour un couple email + IP avant blocage.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 15 * 60;

    /**
     * Connexion en attente du code de double authentification (cle de session).
     */
    private const PENDING_TWO_FACTOR = 'login.two_factor';

    private const TWO_FACTOR_MINUTES = 10;

    public function loginForm(Request $request)
    {
        return view($this->inAdmin($request) ? 'admin.auth.login' : 'auth.login');
    }

    /**
     * Connexion faite depuis /admin (app installee) plutot que depuis la boutique.
     */
    private function inAdmin(Request $request): bool
    {
        return $request->is('admin', 'admin/*');
    }

    private function loginRoute(Request $request): string
    {
        return $this->inAdmin($request) ? 'admin.login' : 'login';
    }

    public function login(Request $request, TwoFactorService $twoFactor)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login|' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Trop de tentatives. Réessayez dans {$minutes} minute(s)."]);
        }

        $user = Auth::validate($credentials) ? Auth::getLastAttempted() : null;

        // Un compte client n'ouvre pas de session depuis la connexion de l'admin
        if (! $user || ($this->inAdmin($request) && ! $user->is_admin)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Identifiants incorrects.']);
        }

        RateLimiter::clear($throttleKey);

        if ($user->hasTwoFactorEnabled() && ! $twoFactor->isTrustedDevice($request, $user)) {
            // Mot de passe correct, mais rien n'est ouvert tant que le code n'est pas saisi
            $request->session()->put(self::PENDING_TWO_FACTOR, [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires' => now()->addMinutes(self::TWO_FACTOR_MINUTES)->timestamp,
            ]);

            return redirect()->route($this->loginRoute($request) . '.two-factor');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function twoFactorForm(Request $request)
    {
        if (! $this->pendingTwoFactorUser($request)) {
            return redirect()->route($this->loginRoute($request));
        }

        return view($this->inAdmin($request) ? 'admin.auth.two-factor' : 'auth.two-factor', [
            'trustDays' => TwoFactorService::TRUST_DAYS,
        ]);
    }

    public function twoFactorVerify(Request $request, TwoFactorService $twoFactor)
    {
        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route($this->loginRoute($request))
                ->withErrors(['email' => 'La vérification a expiré, reconnectez-vous.']);
        }

        $request->validate(['code' => 'required|string|max:20'], ['code.required' => 'Saisissez le code.']);

        $throttleKey = 'two-factor|' . $user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $request->session()->forget(self::PENDING_TWO_FACTOR);
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return redirect()->route($this->loginRoute($request))
                ->withErrors(['email' => "Trop de codes incorrects. Réessayez dans {$minutes} minute(s)."]);
        }

        // 6 chiffres : code de l'application ; sinon, code de secours
        $code = preg_replace('/\s+/', '', $request->code);
        $usedRecoveryCode = ! preg_match('/^\d{6}$/', $code);

        $valid = $usedRecoveryCode
            ? $twoFactor->useRecoveryCode($user, $code)
            : $twoFactor->verifyCode($user, $code);

        if (! $valid) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        RateLimiter::clear($throttleKey);
        $pending = $request->session()->pull(self::PENDING_TWO_FACTOR);
        $trust = $request->boolean('trust');

        if ($trust) {
            Cookie::queue($twoFactor->trustCookie($user));
        }

        $response = $this->completeLogin($request, $user, $pending['remember'] || $trust);

        if ($usedRecoveryCode) {
            $remaining = count($user->two_factor_recovery_codes ?? []);
            $response->with('warning', "Code de secours utilisé : il vous en reste {$remaining}.");
        }

        return $response;
    }

    private function pendingTwoFactorUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING_TWO_FACTOR);

        if (! $pending || $pending['expires'] < now()->timestamp) {
            $request->session()->forget(self::PENDING_TWO_FACTOR);

            return null;
        }

        return User::find($pending['id']);
    }

    private function completeLogin(Request $request, User $user, bool $remember)
    {
        if ($user->hasTwoFactorEnabled()) {
            // « Se souvenir de moi » ne garde pas la session au-dela des 90 jours de confiance
            Auth::guard()->setRememberDuration(TwoFactorService::TRUST_DAYS * 24 * 60);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->is_admin) {
            $this->alertAdminLogin($request);
        }

        $home = $user->is_admin ? route('admin.orders.index') : route('account.index');

        if (! $this->inAdmin($request)) {
            return redirect()->intended($home);
        }

        // Depuis l'admin, on ne reprend une page demandee que si elle est dans l'admin
        $intended = (string) $request->session()->pull('url.intended');
        $path = (string) parse_url($intended, PHP_URL_PATH);

        return redirect($path === '/admin' || str_starts_with($path, '/admin/') ? $intended : $home);
    }

    /**
     * Previent l'admin (push + email) de toute connexion a son compte.
     */
    private function alertAdminLogin(Request $request): void
    {
        $user = $request->user();
        $device = self::describeDevice((string) $request->userAgent());
        $ip = (string) $request->ip();

        app(AdminPushService::class)->notifyAdminLogin($user, $device, $ip);

        try {
            Mail::to($user->email)->send(new AdminLoginAlert($user, $device, $ip, now()->format('d/m/Y à H:i')));
        } catch (\Exception $e) {
            Log::error('Erreur envoi alerte de connexion admin', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Resume lisible d'un user-agent : « Safari sur iPhone », « Chrome sur Windows »…
     */
    public static function describeDevice(string $userAgent): string
    {
        $systems = ['iPhone' => 'iPhone', 'iPad' => 'iPad', 'Android' => 'Android', 'Windows' => 'Windows', 'Macintosh' => 'Mac', 'Linux' => 'Linux'];
        // L'ordre compte : Edge et Chrome se presentent aussi comme Safari
        $browsers = ['Edg' => 'Edge', 'Firefox' => 'Firefox', 'FxiOS' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome' => 'Chrome', 'Safari' => 'Safari'];

        $find = function (array $names) use ($userAgent): ?string {
            foreach ($names as $needle => $name) {
                if (str_contains($userAgent, $needle)) {
                    return $name;
                }
            }

            return null;
        };

        return implode(' sur ', array_filter([$find($browsers), $find($systems)])) ?: 'Appareil inconnu';
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);

        return redirect()->route('account.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($this->inAdmin($request) ? 'admin.login' : 'home');
    }

    public function forgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = PasswordFacade::sendResetLink($request->only('email'));

        return $status === PasswordFacade::RESET_LINK_SENT
            ? back()->with('status', 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.')
            : back()->withInput()->withErrors(['email' => 'Aucun compte trouvé avec cette adresse e-mail.']);
    }

    public function resetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $status = PasswordFacade::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === PasswordFacade::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé.')
            : back()->withErrors(['email' => 'Ce lien de réinitialisation est invalide ou a expiré.']);
    }
}
