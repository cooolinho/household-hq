<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FixedCostReminderMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $fixedCostName,
        public readonly string $fixedCostUrl,
        public readonly float  $fixedCostAmount,
        public readonly string $dueDate,
        public readonly string $leadTimeLabel,
    )
    {
        $this->to($this->recipientEmail, $this->recipientName);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erinnerung: ' . $this->fixedCostName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fixed-cost-reminder',
            with: [
                'recipientName' => $this->recipientName,
                'fixedCostName' => $this->fixedCostName,
                'fixedCostUrl' => $this->fixedCostUrl,
                'fixedCostAmount' => $this->fixedCostAmount,
                'dueDate' => $this->dueDate,
                'leadTimeLabel' => $this->leadTimeLabel,
            ],
        );
    }
}

