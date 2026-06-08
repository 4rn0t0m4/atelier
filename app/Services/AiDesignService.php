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
                . "L'utilisateur décrit le thème qu'il souhaite. Tu dois générer un prompt DALL-E en anglais. "
                . "\n\nSTYLE OBLIGATOIRE (inspire-toi des thèmes existants) :\n"
                . "- Dessin au TRAIT NOIR sur fond BLANC UNIQUEMENT (monochrome, pas de couleur, pas de nuances de gris, pas d'ombrage)\n"
                . "- Lignes fines et délicates, style gravure sur bois\n"
                . "- Composition en COURONNE CIRCULAIRE ou cadre géométrique (hexagone) entourant un espace vide au centre pour le texte\n"
                . "- Le centre doit rester VIDE (pas de texte, pas de lettre, pas de mot)\n"
                . "\nTHÈMES EXISTANTS comme référence :\n"
                . "1. Couronne de fleurs des champs sauvages (coquelicots, marguerites)\n"
                . "2. Cadre hexagonal minimaliste avec feuillage et étoiles\n"
                . "3. Cadre hexagonal avec roses détaillées et feuilles\n"
                . "4. Couronne de roses et anémones, style élégant\n"
                . "5. Couronne avec animaux mignons (panda, lion) et motifs espace/planètes\n"
                . "6. Couronne avec oursons en peluche, nuages, étoiles, montgolfière\n"
                . "7. Couronne de feuilles tropicales (monstera) avec dinosaure\n"
                . "8. Couronne avec animaux de la forêt (renard, hibou, biche, écureuil)\n"
                . "\nRÈGLES :\n"
                . "- Ne génère QUE le prompt DALL-E, sans explication\n"
                . "- Le prompt doit commencer par : 'A circular wreath design for wood engraving, black line art on pure white background,'\n"
                . "- Insiste sur : no text, no letters, no words, empty center, thin delicate lines, black ink style\n"
                . "- Le résultat doit ressembler à un dessin qu'on pourrait graver au laser sur du bois",
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
