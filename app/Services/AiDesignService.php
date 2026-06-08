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
                . "\n\nSTYLE OBLIGATOIRE — MINIMALISTE ET ÉPURÉ :\n"
                . "- Dessin au TRAIT NOIR sur fond BLANC UNIQUEMENT (monochrome, pas de couleur, pas de gris, pas d'ombrage, pas de hachures)\n"
                . "- Lignes FINES et SIMPLES, style line art minimaliste pour gravure laser sur bois\n"
                . "- PEU D'ÉLÉMENTS : maximum 5 à 8 motifs simples disposés en couronne circulaire\n"
                . "- Chaque motif doit être un CONTOUR SIMPLE, pas de détails complexes ni de remplissage\n"
                . "- Composition en COURONNE CIRCULAIRE aérée avec de l'espace entre les éléments\n"
                . "- Le centre doit rester COMPLÈTEMENT VIDE (pas de texte, pas de lettre, pas de mot)\n"
                . "- Aspect AÉRÉ et LÉGER, beaucoup d'espace blanc entre les motifs\n"
                . "\nRÈGLES :\n"
                . "- Ne génère QUE le prompt, sans explication\n"
                . "- Le prompt doit commencer par : 'A minimalist circular wreath design, simple black line art on pure white background, very few elements,'\n"
                . "- Insiste sur : no text, no letters, no words, empty center, simple outlines only, no shading, no crosshatching, no fill, sparse arrangement, clean minimalist style\n"
                . "- Le résultat doit être suffisamment simple pour être gravé au laser sur du bois",
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
