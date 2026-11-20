<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLoginAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $device,
        public string $ip,
        public string $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nouvelle connexion à l'admin — {$this->device}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-login-alert',
        );
    }
}
