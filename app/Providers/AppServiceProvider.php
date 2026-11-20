<?php

namespace App\Providers;

use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Minishlink\WebPush\WebPush;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WebPush::class, fn () => new WebPush(
            ['VAPID' => [
                'subject' => config('services.webpush.subject') ?: 'mailto:' . config('mail.admin_address'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ]],
            // Une commande doit rester notifiable 24 h si le telephone est eteint
            ['TTL' => 86400, 'urgency' => 'high'],
            // L'envoi a lieu pendant la confirmation de commande : ne jamais la bloquer
            new Client(['timeout' => 5, 'connect_timeout' => 3]),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.layouts.bottom-nav', function ($view) {
            $view->with('processingCount', Order::where('status', 'processing')->count());
        });
    }
}
