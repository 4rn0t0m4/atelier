<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendReviewRequests extends Command
{
    protected $signature = 'orders:send-review-requests {--limit=10 : Nombre max d\'emails à envoyer}';

    protected $description = 'Envoie un email de demande d\'avis 7 jours après l\'expédition';

    public function handle(): void
    {
        $apiKey = config('services.brevo.api_key');

        if (! $apiKey) {
            $this->error('BREVO_API_KEY manquante dans .env');
            return;
        }

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
                $html = view('emails.orders.review-request', ['order' => $order])->render();

                $response = Http::withHeaders([
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => "Atelier d'Aubin",
                        'email' => 'contact@atelier-aubin.fr',
                    ],
                    'to' => [
                        ['email' => $order->billing_email, 'name' => $order->billing_first_name],
                    ],
                    'bcc' => [
                        ['email' => 'contact@atelier-aubin.fr'],
                    ],
                    'subject' => 'Votre avis nous intéresse !',
                    'htmlContent' => $html,
                ]);

                if ($response->successful()) {
                    $order->update(['review_requested_at' => now()]);
                    $sent++;
                    $this->info("  OK #{$order->number} -> {$order->billing_email}");
                } else {
                    $this->error("  ERREUR #{$order->number} : {$response->body()}");
                    Log::error("Brevo: échec envoi review #{$order->number}", ['body' => $response->json()]);
                }
            } catch (\Exception $e) {
                $this->error("  ERREUR #{$order->number} : {$e->getMessage()}");
            }
        }

        $this->info("Terminé — {$sent} email(s) envoyé(s).");
    }
}
