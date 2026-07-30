<?php

namespace App\Jobs;

use App\Mail\InsuranceMoveNotificationMail;
use App\Models\Financial\Insurance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class InsuranceMoveNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int    $insuranceId,
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $subject,
        public readonly string $body,
    )
    {
    }

    public function handle(): void
    {
        $insurance = Insurance::query()->find($this->insuranceId);

        if (!$insurance instanceof Insurance) {
            return;
        }

        Mail::to($this->recipientEmail)->send(new InsuranceMoveNotificationMail(
            recipientName: $this->recipientName,
            mailSubject: $this->subject,
            body: $this->body,
            insuranceName: (string)$insurance->{Insurance::name},
        ));
    }
}

