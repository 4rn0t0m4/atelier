<?php

namespace App\Console\Commands\Migrate;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WpMediaToLocal extends Command
{
    protected $signature = 'migrate:wp-media-local
                            {--wp-path= : Chemin local du site WordPress (ex: /home/user/www)}
                            {--dry-run : Afficher sans modifier}';

    protected $description = 'Copie les images WordPress dans le storage Laravel et met à jour la base';

    public function handle(): int
    {
        $wpPath = $this->option('wp-path');
        $dryRun = $this->option('dry-run');

        $medias = Media::where('path', 'like', 'wp-content/uploads/%')->get();
        $this->info("Médias à migrer : {$medias->count()}");

        if ($dryRun) {
            $this->warn('Mode dry-run — aucune modification.');
        }

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($medias->count());

        foreach ($medias as $media) {
            $bar->advance();

            $relativePath = str_replace('wp-content/uploads/', '', $media->path);
            $newPath = 'media/' . $relativePath;

            if (Storage::disk('public')->exists($newPath)) {
                $media->update([
                    'disk' => 'public',
                    'path' => $newPath,
                    'url' => '/storage/' . $newPath,
                ]);
                $migrated++;
                continue;
            }

            if ($dryRun) {
                $skipped++;
                continue;
            }

            $contents = $this->getFileContents($media, $wpPath);

            if (!$contents) {
                $this->newLine();
                $this->error("  Introuvable : {$media->path}");
                $errors++;
                continue;
            }

            Storage::disk('public')->put($newPath, $contents);

            $media->update([
                'disk' => 'public',
                'path' => $newPath,
                'url' => '/storage/' . $newPath,
            ]);

            $migrated++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Migrés : {$migrated} | Ignorés : {$skipped} | Erreurs : {$errors}");

        return self::SUCCESS;
    }

    private function getFileContents(Media $media, ?string $wpPath): ?string
    {
        if ($wpPath) {
            $localFile = rtrim($wpPath, '/') . '/' . $media->path;
            if (file_exists($localFile)) {
                return file_get_contents($localFile);
            }
        }

        $url = 'https://www.atelier-aubin.fr/' . $media->path;

        try {
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
        }

        return null;
    }
}
