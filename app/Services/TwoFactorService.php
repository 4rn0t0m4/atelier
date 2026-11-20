<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Double authentification par application (Google Authenticator, etc.).
 */
class TwoFactorService
{
    public const TRUST_COOKIE = 'trusted_device';

    /**
     * Duree pendant laquelle un appareil de confiance n'a plus a saisir de code.
     */
    public const TRUST_DAYS = 90;

    private const RECOVERY_CODES = 8;

    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Lien otpauth:// lu par l'application d'authentification (QR code ou appui direct).
     */
    public function otpauthUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl("Atelier d'Aubin", $user->email, $secret);
    }

    public function qrCodeSvg(string $otpauthUrl): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd)))->writeString($otpauthUrl);

        // Le SVG est insere dans la page : on retire l'entete XML
        return trim(substr($svg, strpos($svg, '<svg')));
    }

    /**
     * Code saisi pendant l'activation, avant que le secret soit enregistre.
     */
    public function verifySetupCode(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, self::digits($code));
    }

    public function enable(User $user, string $secret): array
    {
        $codes = $this->newRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map([self::class, 'hashRecoveryCode'], $codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_timestep' => $this->google2fa->getTimestamp(),
        ])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_timestep' => null,
        ])->save();
    }

    /**
     * @return string[] les nouveaux codes, en clair : ils ne sont affiches qu'une fois
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->newRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => array_map([self::class, 'hashRecoveryCode'], $codes)])->save();

        return $codes;
    }

    /**
     * Verifie un code a 6 chiffres. Un code deja accepte est refuse (rejeu).
     */
    public function verifyCode(User $user, string $code): bool
    {
        $timestep = $this->google2fa->verifyKeyNewer(
            $user->two_factor_secret,
            self::digits($code),
            $user->two_factor_timestep,
        );

        if ($timestep === false) {
            return false;
        }

        $user->forceFill([
            'two_factor_timestep' => is_int($timestep) ? $timestep : $this->google2fa->getTimestamp(),
        ])->save();

        return true;
    }

    /**
     * Consomme un code de secours : chacun ne fonctionne qu'une fois.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $hash = self::hashRecoveryCode($code);
        $stored = $user->two_factor_recovery_codes ?? [];

        foreach ($stored as $index => $candidate) {
            if (hash_equals($candidate, $hash)) {
                unset($stored[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($stored)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Cookie « appareil de confiance » : dispense de code pendant 90 jours.
     * Sa signature depend du mot de passe et du secret : changer l'un ou
     * l'autre revoque d'un coup tous les appareils de confiance.
     */
    public function trustCookie(User $user): Cookie
    {
        $expires = now()->addDays(self::TRUST_DAYS)->timestamp;

        return cookie(
            self::TRUST_COOKIE,
            implode('|', [$user->id, $expires, $this->trustSignature($user, $expires)]),
            self::TRUST_DAYS * 24 * 60,
        );
    }

    public function isTrustedDevice(Request $request, User $user): bool
    {
        $parts = explode('|', (string) $request->cookie(self::TRUST_COOKIE));

        if (count($parts) !== 3) {
            return false;
        }

        [$userId, $expires, $signature] = $parts;

        return (int) $userId === $user->id
            && (int) $expires > now()->timestamp
            && hash_equals($this->trustSignature($user, (int) $expires), $signature);
    }

    private function trustSignature(User $user, int $expires): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$user->id, $expires, $user->password, $user->two_factor_secret]),
            (string) config('app.key'),
        );
    }

    /**
     * @return string[]
     */
    private function newRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::lower(Str::random(5)) . '-' . Str::lower(Str::random(5)),
            range(1, self::RECOVERY_CODES),
        );
    }

    private static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', Str::lower(preg_replace('/\s+/', '', $code)));
    }

    private static function digits(string $code): string
    {
        return preg_replace('/\D+/', '', $code);
    }
}
