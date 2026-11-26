<?php

namespace App\Mail;

use App\Models\OrderReshipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation au client : reexpedition reglee, nouveau relais enregistre.
 */
class ReshipmentPaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReshipment $reshipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Réexpédition confirmée — commande #{$this->reshipment->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.reshipment-paid',
            with: ['order' => $this->reshipment->order],
        );
    }
}
