<?php

namespace App\Notifications\Financial;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Mail\FixedCostReminderMail;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class FixedCostReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FixedCost         $fixedCost,
        public readonly FixedCostReminder $reminder,
    )
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->reminder->{FixedCostReminder::send_mail} && filled($notifiable->{User::email} ?? null)) {
            $channels[] = 'mail';
        }

        if ($this->reminder->{FixedCostReminder::send_notification}) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): Mailable
    {
        $dueDate = $this->fixedCost->{FixedCost::next_booking_date}?->format('d.m.Y') ?? '-';
        $recipientEmail = (string)($notifiable->{User::email} ?? '');
        $recipientName = (string)($notifiable->{User::name} ?? '');
        $fixedCostUrl = $this->fixedCost->getKey()
            ? FixedCostResource::getUrl(FixedCostResource::PAGE_VIEW, [
                'record' => $this->fixedCost->getKey(),
            ])
            : '';

        return new FixedCostReminderMail(
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            fixedCostName: (string)$this->fixedCost->{FixedCost::name},
            fixedCostUrl: $fixedCostUrl,
            fixedCostAmount: (float)$this->fixedCost->{FixedCost::amount},
            dueDate: $dueDate,
            leadTimeLabel: FixedCostReminder::leadTimeLabel((int)$this->reminder->{FixedCostReminder::days_before}),
        );
    }

    /**
     * Dieses Array wird in der Tabelle "notifications" abgelegt
     *
     * @param object $notifiable
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        return [
            'fixed_cost_id' => $this->fixedCost->getKey(),
            'fixed_cost_name' => $this->fixedCost->{FixedCost::name},
            'fixed_cost_due_date' => $this->fixedCost->{FixedCost::next_booking_date}?->toDateString(),
            'days_before' => $this->reminder->{FixedCostReminder::days_before},
            'lead_time_label' => FixedCostReminder::leadTimeLabel((int)$this->reminder->{FixedCostReminder::days_before}),
            'channels_label' => FixedCostReminder::channelsLabel((bool)$this->reminder->{FixedCostReminder::send_mail}, (bool)$this->reminder->{FixedCostReminder::send_notification}),
        ];
    }
}

