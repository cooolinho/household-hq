<?php

namespace App\Notifications\Financial;

use App\Filament\Admin\Resources\Financial\Budgets\BudgetResource;
use App\Mail\BudgetThresholdMail;
use App\Models\Financial\Budget;
use App\Models\User;
use App\Services\Budget\BudgetCalculation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class BudgetThresholdNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Budget            $budget,
        public readonly BudgetCalculation $calculation,
    )
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->budget->{Budget::send_mail} && filled($notifiable->{User::email} ?? null)) {
            $channels[] = 'mail';
        }

        if ($this->budget->{Budget::send_notification}) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): Mailable
    {
        return new BudgetThresholdMail(
            recipientEmail: (string)($notifiable->{User::email} ?? ''),
            recipientName: (string)($notifiable->{User::name} ?? ''),
            budgetName: (string)$this->budget->{Budget::name},
            budgetUrl: $this->budgetUrl(),
            statusLabel: $this->calculation->status->label(),
            periodLabel: $this->calculation->periodLabel(),
            currency: (string)$this->budget->{Budget::currency},
            limit: $this->calculation->limit,
            spent: $this->calculation->spent,
            remaining: $this->calculation->remaining,
            percentage: $this->calculation->percentage,
            projected: $this->calculation->projected,
        );
    }

    private function budgetUrl(): string
    {
        if ($this->budget->getKey() === null) {
            return '';
        }

        return BudgetResource::getUrl('view', ['record' => $this->budget->getKey()]);
    }

    /**
     * Dieses Array wird in der Tabelle "notifications" abgelegt
     */
    public function toArray(object $notifiable): array
    {
        return [
            'budget_id' => $this->budget->getKey(),
            'budget_name' => $this->budget->{Budget::name},
            'budget_url' => $this->budgetUrl(),
            'level' => $this->calculation->status->name,
            'level_label' => $this->calculation->status->label(),
            'color' => $this->calculation->status->color(),
            'period_label' => $this->calculation->periodLabel(),
            'period_start' => $this->calculation->periodStart->toDateString(),
            'period_end' => $this->calculation->periodEnd->toDateString(),
            'currency' => $this->budget->{Budget::currency},
            'limit' => round($this->calculation->limit, 2),
            'spent' => round($this->calculation->spent, 2),
            'remaining' => round($this->calculation->remaining, 2),
            'percentage' => round($this->calculation->percentage, 2),
            'projected' => round($this->calculation->projected, 2),
        ];
    }
}
