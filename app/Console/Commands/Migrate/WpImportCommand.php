<?php

namespace App\Console\Commands\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

abstract class WpImportCommand extends Command
{
    protected function wp(): \Illuminate\Database\Connection
    {
        return DB::connection('wp_legacy');
    }

    protected function postMeta(int $postId): array
    {
        return $this->wp()
            ->table('postmeta')
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }

    protected function getMeta(int $postId, string $key, mixed $default = null): mixed
    {
        return $this->wp()
            ->table('postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', $key)
            ->value('meta_value') ?? $default;
    }

    protected function safeTruncate(string $table): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function saveMap(string $filename, array $map): void
    {
        file_put_contents(storage_path($filename), json_encode($map, JSON_PRETTY_PRINT));
    }

    protected function loadMap(string $filename): array
    {
        $path = storage_path($filename);

        if (! file_exists($path)) {
            $this->error("Fichier de mapping introuvable : {$filename}. Exécutez d'abord la commande correspondante.");
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    protected function printResult(string $entity, int $created, int $skipped = 0): void
    {
        $this->info("  {$entity} : {$created} créé(s)" . ($skipped ? ", {$skipped} ignoré(s)" : ''));
    }
}
