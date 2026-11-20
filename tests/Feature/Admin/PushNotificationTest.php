<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\AdminPushService;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Mockery;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://push.example.com/send/abc123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.webpush.public_key' => 'cle-publique', 'services.webpush.private_key' => 'cle-privee']);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function subscriptionFor(User $user, string $endpoint = self::ENDPOINT): PushSubscription
    {
        return PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
            'public_key' => 'p256dh-key',
            'auth_token' => 'auth-token',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    /**
     * Remplace l'envoi reel : chaque abonnement mis en file recoit le code HTTP voulu.
     *
     * @param  array<string, int>  $statusByEndpoint
     */
    private function fakeWebPush(array $statusByEndpoint, array &$queued): void
    {
        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andReturnUsing(
            function (Subscription $subscription, string $payload, array $options) use (&$queued) {
                $queued[] = ['endpoint' => $subscription->getEndpoint(), 'payload' => json_decode($payload, true), 'options' => $options];
            }
        );
        $webPush->shouldReceive('flush')->andReturnUsing(function () use (&$queued, $statusByEndpoint) {
            foreach ($queued as $notification) {
                $status = $statusByEndpoint[$notification['endpoint']] ?? 201;
                yield new MessageSentReport(
                    new Request('POST', $notification['endpoint']),
                    new Response($status),
                    $status < 300,
                );
            }
        });

        $this->app->instance(WebPush::class, $webPush);
    }

    public function test_admin_can_subscribe_a_device(): void
    {
        $admin = $this->admin();
        $payload = ['endpoint' => self::ENDPOINT, 'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'], 'contentEncoding' => 'aes128gcm'];

        $this->actingAs($admin)->postJson(route('admin.push.subscribe'), $payload)->assertOk();
        // Le meme appareil se resynchronise regulierement : pas de doublon
        $this->actingAs($admin)->postJson(route('admin.push.subscribe'), $payload)->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertSame($admin->id, PushSubscription::first()->user_id);
    }

    public function test_subscription_requires_an_admin(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($customer)->postJson(route('admin.push.subscribe'), [
            'endpoint' => self::ENDPOINT,
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ])->assertForbidden();

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_subscription_rejects_a_non_https_endpoint(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.push.subscribe'), [
            'endpoint' => 'http://push.example.com/send/abc123',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ])->assertJsonValidationErrors('endpoint');
    }

    public function test_admin_can_unsubscribe_a_device(): void
    {
        $admin = $this->admin();
        $this->subscriptionFor($admin);

        $this->actingAs($admin)->deleteJson(route('admin.push.unsubscribe'), ['endpoint' => self::ENDPOINT])->assertOk();

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_new_order_is_pushed_to_admin_devices_only(): void
    {
        $this->subscriptionFor($this->admin());
        // Ancien admin dont l'appareil est reste abonne
        $this->subscriptionFor(User::factory()->create(['is_admin' => false]), 'https://push.example.com/send/ancien');

        $order = Order::factory()->processing()->create([
            'total' => 1057.90,
            'billing_first_name' => 'Marie',
            'billing_last_name' => 'Dupont',
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_name' => 'Planche gravée', 'quantity' => 2]);

        $queued = [];
        $this->fakeWebPush([], $queued);

        $delivered = app(AdminPushService::class)->notifyNewOrder($order);

        $this->assertSame(1, $delivered);
        $this->assertCount(1, $queued);
        $this->assertSame(self::ENDPOINT, $queued[0]['endpoint']);
        $this->assertSame(['topic' => 'order-' . $order->id], $queued[0]['options']);

        $payload = $queued[0]['payload'];
        $this->assertSame('Nouvelle commande · 1 057,90 €', $payload['title']);
        $this->assertStringContainsString('Marie Dupont', $payload['body']);
        $this->assertStringContainsString('2 × Planche gravée', $payload['body']);
        $this->assertSame("/admin/commandes?commande={$order->id}", $payload['url']);
        $this->assertSame(1, $payload['badgeCount']);
    }

    public function test_expired_subscription_is_removed(): void
    {
        $admin = $this->admin();
        $this->subscriptionFor($admin);
        $this->subscriptionFor($admin, 'https://push.example.com/send/desinstalle');

        $queued = [];
        $this->fakeWebPush(['https://push.example.com/send/desinstalle' => 410], $queued);

        $delivered = app(AdminPushService::class)->notifyNewOrder(Order::factory()->processing()->create());

        $this->assertSame(1, $delivered);
        $this->assertSame([self::ENDPOINT], PushSubscription::pluck('endpoint')->all());
    }

    public function test_push_failure_never_breaks_the_order_flow(): void
    {
        $this->subscriptionFor($this->admin());

        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andThrow(new \ErrorException('cle invalide'));
        $this->app->instance(WebPush::class, $webPush);

        $this->assertSame(0, app(AdminPushService::class)->notifyNewOrder(Order::factory()->processing()->create()));
    }

    public function test_nothing_is_sent_without_vapid_keys(): void
    {
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);
        $admin = $this->admin();
        $this->subscriptionFor($admin);

        $this->app->instance(WebPush::class, Mockery::mock(WebPush::class)->shouldNotReceive('queueNotification')->getMock());

        $this->assertSame(0, app(AdminPushService::class)->notifyNewOrder(Order::factory()->processing()->create()));
        $this->actingAs($admin)->postJson(route('admin.push.test'))->assertStatus(503);
    }

    public function test_test_notification_targets_the_current_admin(): void
    {
        $admin = $this->admin();
        $this->subscriptionFor($admin);
        $this->subscriptionFor($this->admin(), 'https://push.example.com/send/autre-admin');

        $queued = [];
        $this->fakeWebPush([], $queued);

        $this->actingAs($admin)->postJson(route('admin.push.test'))->assertOk()->assertJsonStructure(['success']);

        $this->assertSame([self::ENDPOINT], array_column($queued, 'endpoint'));
    }
}
