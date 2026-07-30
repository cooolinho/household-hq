<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InsuranceMoveNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $mailSubject,
        public readonly string $body,
        public readonly string $insuranceName,
    )
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.insurance-move-notification',
            with: [
                'recipientName' => $this->recipientName,
                'body' => $this->body,
                'insuranceName' => $this->insuranceName,
            ],
        );
    }
}

