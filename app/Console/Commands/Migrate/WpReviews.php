<?php

namespace App\Console\Commands\Migrate;

use App\Models\ProductReview;

class WpReviews extends WpImportCommand
{
    protected $signature = 'migrate:wp-reviews';
    protected $description = 'Importe les avis produit depuis WooCommerce';

    public function handle(): int
    {
        $this->info('Import des avis produit...');
        $this->safeTruncate('product_reviews');

        $productMap = $this->loadMap('wp_product_map.json');
        $userMap = $this->loadMap('wp_user_map.json');

        $reviews = $this->wp()
            ->table('comments')
            ->where('comment_type', 'review')
            ->orderBy('comment_ID')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($reviews as $r) {
            $laravelProductId = $productMap[(int) $r->comment_post_ID] ?? null;

            if (! $laravelProductId) {
                $skipped++;
                continue;
            }

            $rating = (int) $this->wp()
                ->table('commentmeta')
                ->where('comment_id', $r->comment_ID)
                ->where('meta_key', 'rating')
                ->value('meta_value');

            $userId = (int) $r->user_id;

            $review = new ProductReview();
            $review->timestamps = false;
            $review->fill([
                'product_id' => $laravelProductId,
                'user_id' => $userId ? ($userMap[$userId] ?? null) : null,
                'author_name' => mb_substr($r->comment_author, 0, 100),
                'author_email' => $r->comment_author_email,
                'rating' => $rating ?: 5,
                'content' => $r->comment_content,
                'photos' => null,
                'is_approved' => $r->comment_approved === '1',
            ]);
            $review->created_at = $r->comment_date;
            $review->updated_at = $r->comment_date;
            $review->save();

            $created++;
        }

        $this->printResult('Avis', $created, $skipped);

        return self::SUCCESS;
    }
}
