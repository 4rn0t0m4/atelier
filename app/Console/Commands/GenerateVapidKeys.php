<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Génère la paire de clés VAPID des notifications push de l\'admin';

    public function handle(): void
    {
        $keys = VAPID::createVapidKeys();

        $this->line('Ajoutez ces lignes au fichier .env (une seule fois : changer les clés désabonne tous les appareils) :');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
    }
}
