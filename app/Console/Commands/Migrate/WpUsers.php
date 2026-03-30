<?php

namespace App\Console\Commands\Migrate;

use App\Models\User;
use Illuminate\Support\Str;

class WpUsers extends WpImportCommand
{
    protected $signature = 'migrate:wp-users';
    protected $description = 'Importe les utilisateurs depuis WordPress';

    public function handle(): int
    {
        $this->info('Import des utilisateurs WordPress...');
        $this->safeTruncate('users');

        $wpUsers = $this->wp()
            ->table('users')
            ->orderBy('ID')
            ->get();

        $map = [];
        $created = 0;
        $skipped = 0;

        foreach ($wpUsers as $wu) {
            // Vérifier doublon email
            if (User::where('email', $wu->user_email)->exists()) {
                $skipped++;
                continue;
            }

            $meta = $this->wp()
                ->table('usermeta')
                ->where('user_id', $wu->ID)
                ->pluck('meta_value', 'meta_key')
                ->toArray();

            // Déterminer si admin
            $capabilities = @unserialize($meta['mod745_capabilities'] ?? '');
            $isAdmin = is_array($capabilities) && isset($capabilities['administrator']);

            $data = [
                'name' => $wu->display_name ?: $wu->user_login,
                'email' => $wu->user_email,
                'password' => Str::random(32), // Mot de passe temporaire
                'is_admin' => $isAdmin,
                'first_name' => $meta['billing_first_name'] ?? ($meta['first_name'] ?? null),
                'last_name' => $meta['billing_last_name'] ?? ($meta['last_name'] ?? null),
                'phone' => $meta['billing_phone'] ?? null,
                'address_1' => $meta['billing_address_1'] ?? null,
                'address_2' => $meta['billing_address_2'] ?? null,
                'city' => $meta['billing_city'] ?? null,
                'postcode' => $meta['billing_postcode'] ?? null,
                'shipping_first_name' => $meta['shipping_first_name'] ?? null,
                'shipping_last_name' => $meta['shipping_last_name'] ?? null,
                'shipping_address_1' => $meta['shipping_address_1'] ?? null,
                'shipping_address_2' => $meta['shipping_address_2'] ?? null,
                'shipping_city' => $meta['shipping_city'] ?? null,
                'shipping_postcode' => $meta['shipping_postcode'] ?? null,
                'shipping_country' => $meta['shipping_country'] ?? null,
            ];

            // country a un default('FR') en DB, ne pas envoyer null
            if (! empty($meta['billing_country'])) {
                $data['country'] = $meta['billing_country'];
            }

            $user = User::create($data);

            // Préserver les dates originales
            $user->timestamps = false;
            $user->created_at = $wu->user_registered;
            $user->save();
            $user->timestamps = true;

            $map[$wu->ID] = $user->id;
            $created++;
        }

        $this->saveMap('wp_user_map.json', $map);
        $this->printResult('Utilisateurs', $created, $skipped);

        return self::SUCCESS;
    }
}
