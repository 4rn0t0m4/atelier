<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contact) {}

    public function envelope(): Envelope
    {
        $subject = $this->contact->subject
            ? "Contact : {$this->contact->subject}"
            : "Nouveau message de {$this->contact->name}";

        return new Envelope(
            subject: $subject,
            replyTo: [$this->contact->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-admin',
        );
    }
}
