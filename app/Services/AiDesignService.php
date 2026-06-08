<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiDesignService
{
    public function generate(string $description): array
    {
        $imagePrompt = $this->buildImagePrompt($description);

        $localPath = $this->generateImage($imagePrompt);

        return [
            'image_url' => Storage::url($localPath),
            'image_path' => $localPath,
            'prompt_used' => $imagePrompt,
        ];
    }

    private function buildImagePrompt(string $description): string
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 300,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $description,
                ],
            ],
            'system' => "Tu es un expert en design pour des urnes en bois personnalisées pour baptême. "
                . "L'utilisateur décrit le thème qu'il souhaite. Tu dois générer un prompt en anglais pour un générateur d'images. "
                . "\n\nSTYLE OBLIGATOIRE — ÉLÉGANT POUR GRAVURE BOIS :\n"
                . "- Dessin au TRAIT NOIR sur fond BLANC UNIQUEMENT (monochrome, pas de couleur, pas de nuances de gris)\n"
                . "- Lignes fines et nettes, style illustration botanique / naturaliste élégant\n"
                . "- Les motifs doivent avoir un BON NIVEAU DE DÉTAIL (nervures de feuilles, écailles de poisson, pétales détaillés) mais PAS de hachures croisées ni de zones remplies en noir\n"
                . "- Composition en COURONNE CIRCULAIRE harmonieuse, environ 8 à 12 motifs bien répartis\n"
                . "- Le centre doit rester COMPLÈTEMENT VIDE (pas de texte, pas de lettre, pas de mot)\n"
                . "- Équilibre entre détail et lisibilité : chaque motif est reconnaissable et joli, mais l'ensemble reste aéré\n"
                . "\nRÈGLES :\n"
                . "- Ne génère QUE le prompt, sans explication\n"
                . "- Le prompt doit commencer par : 'A circular wreath design for wood engraving, black line art on pure white background,'\n"
                . "- Insiste sur : no text, no letters, no words, empty center, fine detailed lines, no crosshatching, no solid black fills, elegant botanical illustration style\n"
                . "- Le résultat doit ressembler à une illustration élégante qu'on pourrait graver au laser sur du bois",
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erreur API Claude : ' . $response->body());
        }

        return $response->json('content.0.text');
    }

    private function generateImage(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/images/generations', [
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erreur API OpenAI : ' . $response->body());
        }

        $b64 = $response->json('data.0.b64_json');
        if (!$b64) {
            throw new \RuntimeException('Aucune image générée par l\'API.');
        }

        return $this->saveImage(base64_decode($b64));
    }

    private function saveImage(string $contents): string
    {
        $path = 'ai-designs/' . date('Y/m') . '/' . Str::uuid() . '.png';

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}
