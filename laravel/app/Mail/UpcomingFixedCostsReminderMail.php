<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UpcomingFixedCostsReminderMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientName,
        public readonly array $windows,
        public readonly array $ranges,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Upcoming fixed costs reminder',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fixed-costs-reminder',
            with: [
                'recipientName' => $this->recipientName,
                'windows' => $this->windows,
                'ranges' => $this->ranges,
            ],
        );
    }
}

