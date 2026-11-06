<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderConfirmation;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderResendEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_resend_order_confirmation(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['billing_email' => 'cliente@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.resend-email', $order), [
                'type' => 'confirmation',
                'email' => 'cliente@example.com',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        Mail::assertSent(OrderConfirmation::class, fn ($mail) =>
            $mail->hasTo('cliente@example.com') && $mail->order->is($order)
        );
    }

    public function test_resending_to_a_corrected_address_updates_the_order(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['billing_email' => 'faute@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.resend-email', $order), [
                'type' => 'shipped',
                'email' => 'correcte@example.com',
            ]);

        $this->assertSame('correcte@example.com', $order->fresh()->billing_email);
        Mail::assertSent(OrderShipped::class, fn ($mail) => $mail->hasTo('correcte@example.com'));
    }

    public function test_ajax_request_gets_json_response(): void
    {
        Mail::fake();
        $order = Order::factory()->create();

        // Memes en-tetes que le panneau lateral (fetch dans admin/orders/index)
        $this->actingAs($this->admin())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('admin.orders.resend-email', $order), [
                'type' => 'confirmation',
                'email' => $order->billing_email,
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'order_id']);
    }

    public function test_unknown_type_is_rejected(): void
    {
        Mail::fake();
        $order = Order::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.resend-email', $order), [
                'type' => 'payment_failed',
                'email' => $order->billing_email,
            ])
            ->assertSessionHasErrors('type');

        Mail::assertNothingSent();
    }

    public function test_guests_and_non_admins_cannot_resend(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $payload = ['type' => 'confirmation', 'email' => $order->billing_email];

        $this->post(route('admin.orders.resend-email', $order), $payload)
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post(route('admin.orders.resend-email', $order), $payload)
            ->assertForbidden();

        Mail::assertNothingSent();
    }
}
