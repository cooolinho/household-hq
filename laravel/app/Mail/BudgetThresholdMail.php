<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BudgetThresholdMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $budgetName,
        public readonly string $budgetUrl,
        public readonly string $statusLabel,
        public readonly string $periodLabel,
        public readonly string $currency,
        public readonly float  $limit,
        public readonly float  $spent,
        public readonly float  $remaining,
        public readonly float  $percentage,
        public readonly float  $projected,
    )
    {
        $this->to($this->recipientEmail, $this->recipientName);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Budget "%s": %s', $this->budgetName, $this->statusLabel),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.budget-threshold',
            with: [
                'recipientName' => $this->recipientName,
                'budgetName' => $this->budgetName,
                'budgetUrl' => $this->budgetUrl,
                'statusLabel' => $this->statusLabel,
                'periodLabel' => $this->periodLabel,
                'currency' => $this->currency,
                'limit' => $this->limit,
                'spent' => $this->spent,
                'remaining' => $this->remaining,
                'percentage' => $this->percentage,
                'projected' => $this->projected,
            ],
        );
    }
}
