<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;

class DisableTwoFactor extends Command
{
    protected $signature = 'admin:2fa-disable {email : Adresse e-mail du compte}';

    protected $description = 'Désactive la double authentification d\'un compte (téléphone et codes de secours perdus)';

    public function handle(TwoFactorService $twoFactor): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun compte avec cette adresse.');

            return self::FAILURE;
        }

        $twoFactor->disable($user);
        $this->info("Double authentification désactivée pour {$user->email}.");

        return self::SUCCESS;
    }
}
