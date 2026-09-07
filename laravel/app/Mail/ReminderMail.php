<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReminderMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string  $recipientEmail,
        public readonly string  $recipientName,
        public readonly string  $reminderTitle,
        public readonly string  $bodyText,
        public readonly ?string $targetUrl,
        public readonly string  $scheduleLabel,
    )
    {
        $this->to($this->recipientEmail, $this->recipientName);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erinnerung: ' . $this->reminderTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'recipientName' => $this->recipientName,
                'reminderTitle' => $this->reminderTitle,
                'bodyText' => $this->bodyText,
                'targetUrl' => $this->targetUrl,
                'scheduleLabel' => $this->scheduleLabel,
            ],
        );
    }
}
