<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    private const SETUP_SECRET = 'two_factor_setup_secret';

    private const PASSWORD_MESSAGES = [
        'current_password.required' => 'Saisissez votre mot de passe.',
        'current_password.current_password' => 'Mot de passe incorrect.',
    ];

    public function index(Request $request, TwoFactorService $twoFactor)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('admin.security.index', [
                'enabled' => true,
                'recoveryCodes' => session('recovery_codes'),
                'remainingRecoveryCodes' => count($user->two_factor_recovery_codes ?? []),
            ]);
        }

        // Le secret reste en session tant qu'un premier code ne l'a pas confirme
        $secret = $request->session()->get(self::SETUP_SECRET) ?? $twoFactor->generateSecret();
        $request->session()->put(self::SETUP_SECRET, $secret);

        $otpauthUrl = $twoFactor->otpauthUrl($user, $secret);

        return view('admin.security.index', [
            'enabled' => false,
            'secret' => $secret,
            'otpauthUrl' => $otpauthUrl,
            'qrCode' => $twoFactor->qrCodeSvg($otpauthUrl),
        ]);
    }

    public function enable(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'code' => 'required|string|max:10',
        ], self::PASSWORD_MESSAGES + ['code.required' => 'Saisissez le code affiché par l\'application.']);

        $user = $request->user();
        $secret = $request->session()->get(self::SETUP_SECRET);

        if ($user->hasTwoFactorEnabled() || ! $secret) {
            return redirect()->route('admin.security.index');
        }

        if (! $twoFactor->verifySetupCode($secret, $request->code)) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez l\'heure du téléphone et réessayez.']);
        }

        $codes = $twoFactor->enable($user, $secret);
        $request->session()->forget(self::SETUP_SECRET);

        return redirect()->route('admin.security.index')
            ->with('success', 'Double authentification activée.')
            ->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['current_password' => 'required|current_password'], self::PASSWORD_MESSAGES);

        abort_unless($request->user()->hasTwoFactorEnabled(), 404);

        return redirect()->route('admin.security.index')
            ->with('success', 'Nouveaux codes de secours générés : les anciens ne fonctionnent plus.')
            ->with('recovery_codes', $twoFactor->regenerateRecoveryCodes($request->user()));
    }

    public function disable(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['current_password' => 'required|current_password'], self::PASSWORD_MESSAGES);

        $twoFactor->disable($request->user());

        return redirect()->route('admin.security.index')->with('success', 'Double authentification désactivée.');
    }
}
