<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BoxtalAuthService
{
    /**
     * Déchiffre le body d'une requête Boxtal Connect.
     */
    public function decryptBody(string $jsonBody): ?object
    {
        $body = json_decode($jsonBody);

        if (! $body || ! isset($body->encryptedKey, $body->encryptedData)) {
            return null;
        }

        $key = $this->decryptPublicKey($body->encryptedKey);
        if ($key === null) {
            return null;
        }

        $data = $this->rc4(base64_decode($body->encryptedData), $key);

        return json_decode($data);
    }

    public function authenticate(string $rawBody): ?object
    {
        $decrypted = $this->decryptBody($rawBody);

        if ($decrypted === null) {
            Log::warning('BoxtalConnect: requête entrante refusée (401) — déchiffrement échoué');

            return null;
        }

        return $decrypted;
    }

    public function authenticateWithAccessKey(string $rawBody): ?object
    {
        $decrypted = $this->authenticate($rawBody);

        if ($decrypted === null) {
            return null;
        }

        $storedKey = config('shipping.boxtal.connect_access_key')
            ?: \App\Models\Setting::get('boxtal_connect_access_key');

        if (! isset($decrypted->accessKey) || $decrypted->accessKey !== $storedKey) {
            Log::warning('BoxtalConnect: requête entrante refusée (403) — accessKey invalide');

            return null;
        }

        return $decrypted;
    }

    private function decryptPublicKey(string $encryptedKey): ?string
    {
        $publicKeyPath = storage_path('app/boxtal/publickey');

        if (! file_exists($publicKeyPath)) {
            Log::error('BoxtalConnect: clé publique introuvable');

            return null;
        }

        $publicKey = file_get_contents($publicKeyPath);
        $decrypted = '';

        if (openssl_public_decrypt(base64_decode($encryptedKey), $decrypted, $publicKey)) {
            return json_decode($decrypted);
        }

        return null;
    }

    private function rc4(string $data, string $key): string
    {
        $s = range(0, 255);
        $j = 0;
        $keyLen = strlen($key);

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % $keyLen])) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
        }

        $i = 0;
        $j = 0;
        $result = '';

        for ($k = 0; $k < strlen($data); $k++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            [$s[$i], $s[$j]] = [$s[$j], $s[$i]];
            $result .= chr(ord($data[$k]) ^ $s[($s[$i] + $s[$j]) % 256]);
        }

        return $result;
    }
}
