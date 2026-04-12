<?php

namespace App\Console\Commands;

use App\Mail\ReviewRequest;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReviewRequests extends Command
{
    protected $signature = 'orders:send-review-requests {--limit=10 : Nombre max d\'emails à envoyer}';

    protected $description = 'Envoie un email de demande d\'avis 7 jours après l\'expédition';

    public function handle(): void
    {
        $limit = (int) $this->option('limit');

        $orders = Order::whereNotNull('shipped_at')
            ->whereNull('review_requested_at')
            ->where('shipped_at', '<=', now()->subDays(7))
            ->whereIn('status', ['processing', 'shipped', 'completed'])
            ->with(['items.product.featuredImage'])
            ->latest('shipped_at')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande éligible.');
            return;
        }

        $sent = 0;

        foreach ($orders as $order) {
            try {
                Mail::to($order->billing_email)
                    ->bcc('contact@atelier-aubin.fr')
                    ->send(new ReviewRequest($order));
                $order->update(['review_requested_at' => now()]);
                $sent++;
                $this->info("  OK #{$order->number} -> {$order->billing_email}");
            } catch (\Exception $e) {
                $this->error("  ERREUR #{$order->number} : {$e->getMessage()}");
                Log::error("Review request #{$order->number}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Terminé — {$sent} email(s) envoyé(s).");
    }
}
