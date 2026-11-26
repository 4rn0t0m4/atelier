<?php

namespace App\Mail;

use App\Models\OrderReshipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lien envoye au client pour choisir un nouveau relais et regler la reexpedition.
 */
class ReshipmentRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReshipment $reshipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre colis nous a été retourné — commande #{$this->reshipment->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.reshipment-request',
            with: ['order' => $this->reshipment->order],
        );
    }
}
