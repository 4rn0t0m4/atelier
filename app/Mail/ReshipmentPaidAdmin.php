<?php

namespace App\Mail;

use App\Models\OrderReshipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte admin : une reexpedition a ete reglee, nouvelle etiquette a creer.
 */
class ReshipmentPaidAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReshipment $reshipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Réexpédition réglée — commande #{$this->reshipment->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.reshipment-paid-admin',
            with: ['order' => $this->reshipment->order],
        );
    }
}
