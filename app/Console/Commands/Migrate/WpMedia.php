<?php

namespace App\Console\Commands\Migrate;

use App\Models\Media;

class WpMedia extends WpImportCommand
{
    protected $signature = 'migrate:wp-media';
    protected $description = 'Importe les médias (images) depuis WordPress';

    public function handle(): int
    {
        $this->info('Import des médias WordPress...');
        $this->safeTruncate('media');

        $attachments = $this->wp()
            ->table('posts')
            ->where('post_type', 'attachment')
            ->where('post_mime_type', 'like', 'image%')
            ->get();

        $map = [];
        $created = 0;

        foreach ($attachments as $att) {
            $file = $this->getMeta($att->ID, '_wp_attached_file');

            if (! $file) {
                continue;
            }

            $metaRaw = $this->getMeta($att->ID, '_wp_attachment_metadata');
            $meta = $metaRaw ? @unserialize($metaRaw) : [];
            $alt = $this->getMeta($att->ID, '_wp_attachment_image_alt', '');

            $path = 'wp-content/uploads/' . $file;
            $url = 'https://www.atelier-aubin.fr/wp-content/uploads/' . $file;

            $media = Media::create([
                'filename' => basename($file),
                'original_filename' => basename($file),
                'disk' => 'external',
                'path' => $path,
                'url' => $url,
                'mime_type' => $att->post_mime_type,
                'size' => 0,
                'width' => is_array($meta) ? ($meta['width'] ?? null) : null,
                'height' => is_array($meta) ? ($meta['height'] ?? null) : null,
                'alt' => $alt ?: $att->post_title,
                'title' => $att->post_title,
            ]);

            $map[$att->ID] = $media->id;
            $created++;
        }

        $this->saveMap('wp_media_map.json', $map);
        $this->printResult('Médias', $created);

        return self::SUCCESS;
    }
}
