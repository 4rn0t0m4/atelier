<?php

namespace Tests\Feature;

use App\Mail\ReshipmentPaid;
use App\Mail\ReshipmentPaidAdmin;
use App\Mail\ReshipmentRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReshipment;
use App\Models\User;
use App\Services\ReshipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReshipmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function shippedRelayOrder(): Order
    {
        return Order::factory()->processing()->create([
            'status' => 'shipped',
            'shipped_at' => now()->subDays(10),
            'shipping_key' => 'boxtal',
            'shipping_method' => 'Livraison en point relais',
            'relay_point_code' => 'OLD123',
            'relay_network' => 'MONR_NETWORK',
            'boxtal_shipping_order_id' => 'BX-OLD',
            'boxtal_label_url' => 'https://example.test/label.pdf',
            'tracking_number' => 'TRACK-OLD',
            'tracking_carrier' => 'Mondial Relay',
            'customer_note' => 'Point relais : Ancien relais',
        ]);
    }

    // --- Admin ---

    public function test_admin_creates_reshipment_and_sends_link(): void
    {
        Mail::fake();
        $order = $this->shippedRelayOrder();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.reshipments.store', $order), [
                'amount' => '5.00',
                'email' => 'nouvelle@example.test',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $reshipment = OrderReshipment::where('order_id', $order->id)->first();

        $this->assertNotNull($reshipment);
        $this->assertTrue($reshipment->isPending());
        $this->assertEquals(5.00, (float) $reshipment->amount);
        $this->assertEquals('OLD123', $reshipment->previous_relay_point_code);
        $this->assertEquals('BX-OLD', $reshipment->previous_boxtal_shipping_order_id);
        $this->assertEquals('TRACK-OLD', $reshipment->previous_tracking_number);
        $this->assertNotNull($reshipment->email_sent_at);
        $this->assertEquals('nouvelle@example.test', $order->fresh()->billing_email);

        Mail::assertSent(ReshipmentRequest::class, fn ($mail) => $mail->hasTo('nouvelle@example.test'));
    }

    public function test_creating_a_new_request_cancels_the_pending_one(): void
    {
        Mail::fake();
        $order = $this->shippedRelayOrder();
        $old = OrderReshipment::factory()->create(['order_id' => $order->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.reshipments.store', $order), ['amount' => '6.90', 'email' => $order->billing_email]);

        $this->assertTrue($old->fresh()->isCancelled());
        $this->assertEquals(1, OrderReshipment::where('order_id', $order->id)->where('status', 'pending')->count());
    }

    public function test_admin_can_cancel_and_resend(): void
    {
        Mail::fake();
        $order = $this->shippedRelayOrder();
        $reshipment = OrderReshipment::factory()->create(['order_id' => $order->id]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.orders.reshipments.resend', [$order, $reshipment]))
            ->assertRedirect();
        Mail::assertSent(ReshipmentRequest::class);

        $this->actingAs($admin)
            ->delete(route('admin.orders.reshipments.cancel', [$order, $reshipment]))
            ->assertRedirect();
        $this->assertTrue($reshipment->fresh()->isCancelled());
    }

    public function test_reshipment_must_belong_to_the_order(): void
    {
        $order = $this->shippedRelayOrder();
        $other = OrderReshipment::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.orders.reshipments.cancel', [$order, $other]))
            ->assertNotFound();
    }

    public function test_admin_order_page_shows_reshipment_block(): void
    {
        $order = $this->shippedRelayOrder();
        OrderReshipment::factory()->paid()->create(['order_id' => $order->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Réexpédition en relais')
            ->assertSee('Tabac du Centre');
    }

    public function test_guest_cannot_use_admin_routes(): void
    {
        $order = $this->shippedRelayOrder();

        $this->post(route('admin.orders.reshipments.store', $order), ['amount' => '5', 'email' => 'a@b.c'])
            ->assertRedirect();
        $this->assertEquals(0, OrderReshipment::count());
    }

    // --- Page publique ---

    public function test_public_page_shows_relay_picker_for_pending_request(): void
    {
        $order = $this->shippedRelayOrder();
        $reshipment = OrderReshipment::factory()->create(['order_id' => $order->id, 'amount' => 6.90]);

        $this->get(route('reshipment.show', $reshipment->token))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Choisissez votre nouveau point relais')
            ->assertSee('6,90 €')
            ->assertSee('relay_point_code', false);
    }

    public function test_public_page_shows_confirmation_when_paid(): void
    {
        $reshipment = OrderReshipment::factory()->paid()->create();

        $this->get(route('reshipment.show', $reshipment->token))
            ->assertOk()
            ->assertSee('Réexpédition réglée')
            ->assertSee('Tabac du Centre')
            ->assertDontSee('Passer au règlement');
    }

    public function test_public_page_rejects_cancelled_and_unknown_tokens(): void
    {
        $reshipment = OrderReshipment::factory()->cancelled()->create();

        $this->get(route('reshipment.show', $reshipment->token))
            ->assertOk()
            ->assertSee("Ce lien n'est plus valide", false)
            ->assertDontSee('relay_point_code');

        $this->get(route('reshipment.show', 'inconnu'))->assertNotFound();
    }

    public function test_store_requires_a_relay_point(): void
    {
        $reshipment = OrderReshipment::factory()->create();

        $this->from(route('reshipment.show', $reshipment->token))
            ->post(route('reshipment.store', $reshipment->token), ['payment_method' => 'stripe'])
            ->assertRedirect(route('reshipment.show', $reshipment->token))
            ->assertSessionHasErrors(['relay_point_code']);
    }

    public function test_paypal_endpoints_refuse_requests_without_relay_choice(): void
    {
        $reshipment = OrderReshipment::factory()->create();

        $this->postJson(route('reshipment.paypal.create-order', $reshipment->token))->assertForbidden();
        $this->postJson(route('reshipment.paypal.capture-order', $reshipment->token), ['paypal_order_id' => 'X'])->assertForbidden();
    }

    public function test_success_page_without_payment_shows_pending_state(): void
    {
        $reshipment = OrderReshipment::factory()->withRelay()->create();

        $this->get(route('reshipment.success', $reshipment->token))
            ->assertOk()
            ->assertSee('Paiement en cours de vérification');
    }

    // --- Service ---

    public function test_confirm_payment_updates_order_with_new_relay(): void
    {
        Mail::fake();
        $order = $this->shippedRelayOrder();
        OrderItem::factory()->create(['order_id' => $order->id]);
        $reshipment = OrderReshipment::factory()->withRelay()->create(['order_id' => $order->id]);

        $result = app(ReshipmentService::class)->confirmPayment($reshipment);

        $this->assertTrue($result);

        $reshipment->refresh();
        $this->assertTrue($reshipment->isPaid());
        $this->assertNotNull($reshipment->paid_at);

        $order->refresh();
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('FR12345', $order->relay_point_code);
        $this->assertEquals('CHRP_NETWORK', $order->relay_network);
        $this->assertEquals('boxtal', $order->shipping_key);
        $this->assertNull($order->boxtal_shipping_order_id);
        $this->assertNull($order->boxtal_label_url);
        $this->assertNull($order->tracking_number);
        $this->assertNull($order->tracking_carrier);
        $this->assertNull($order->shipped_at);
        $this->assertStringContainsString('Point relais : Ancien relais', $order->customer_note);
        $this->assertStringContainsString('Réexpédition du', $order->customer_note);
        $this->assertStringContainsString('Tabac du Centre', $order->customer_note);

        Mail::assertSent(ReshipmentPaid::class, fn ($mail) => $mail->hasTo($order->billing_email));
        Mail::assertSent(ReshipmentPaidAdmin::class, fn ($mail) => $mail->hasTo(config('mail.admin_address')));
    }

    public function test_confirm_payment_uses_international_relay_outside_france(): void
    {
        Mail::fake();
        $order = $this->shippedRelayOrder();
        $order->update(['shipping_country' => 'BE']);
        $reshipment = OrderReshipment::factory()->withRelay()->create(['order_id' => $order->id]);

        app(ReshipmentService::class)->confirmPayment($reshipment);

        $this->assertEquals('boxtal_intl', $order->fresh()->shipping_key);
    }

    public function test_confirm_payment_is_idempotent(): void
    {
        Mail::fake();
        $reshipment = OrderReshipment::factory()->paid()->create();

        $this->assertFalse(app(ReshipmentService::class)->confirmPayment($reshipment));
        Mail::assertNothingSent();
    }

    public function test_default_amount_depends_on_destination(): void
    {
        $service = app(ReshipmentService::class);

        $fr = Order::factory()->create(['shipping_country' => 'FR']);
        $be = Order::factory()->create(['shipping_country' => 'BE']);

        $this->assertEquals(config('shipping.methods.boxtal.price'), $service->defaultAmount($fr));
        $this->assertEquals(config('shipping.methods.boxtal_intl.price'), $service->defaultAmount($be));
    }
}
