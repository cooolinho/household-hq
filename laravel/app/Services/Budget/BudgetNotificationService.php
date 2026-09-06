<?php

namespace App\Services\Budget;

use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\Budget;
use App\Models\User;
use App\Notifications\Financial\BudgetThresholdNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Prüft Budget-Schwellwerte und verschickt Benachrichtigungen.
 *
 * Pro Zeitraum wird nur eskalierend benachrichtigt (OK -> WARNING -> EXCEEDED);
 * der Zustand liegt auf dem Budget (last_notified_level / last_notified_period_start),
 * analog zu FixedCostReminder::last_sent_booking_date.
 */
final class BudgetNotificationService
{
    public function __construct(
        private readonly BudgetCalculationService $calculationService,
    )
    {
    }

    /**
     * @return int Anzahl der versendeten Benachrichtigungen
     */
    public function checkForUser(int $userId, ?CarbonImmutable $reference = null): int
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            Log::channel('database')->warning('Budget-Benachrichtigung konnte nicht zugestellt werden.', [
                'event' => 'budget.threshold.notification_skipped',
                'user_id' => $userId,
            ]);

            return 0;
        }

        $budgets = Budget::query()
            ->activeForUser($userId)
            ->with(Budget::belongs_to_many_transaction_categories)
            ->get();

        $sent = 0;

        foreach ($budgets as $budget) {
            if ($this->checkBudget($user, $budget, $reference)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function checkBudget(User $user, Budget $budget, ?CarbonImmutable $reference = null): bool
    {
        $calculation = $this->calculationService->calculate($budget, $reference);
        $level = $calculation->status;

        if ($level === BudgetStatusEnum::OK) {
            $this->persistLevel($budget, $calculation, BudgetStatusEnum::OK);

            return false;
        }

        if ($level->severity() <= $this->notifiedSeverity($budget, $calculation)) {
            return false;
        }

        if (!$budget->send_mail && !$budget->send_notification) {
            $this->persistLevel($budget, $calculation, $level);

            return false;
        }

        $user->notify(new BudgetThresholdNotification($budget, $calculation));
        $this->persistLevel($budget, $calculation, $level);

        Log::channel('database')->info('Budget-Schwellwert erreicht, Benachrichtigung versendet.', [
            'event' => 'budget.threshold.notified',
            'user_id' => $user->getKey(),
            'budget_id' => $budget->getKey(),
            'level' => $level->name,
            'percentage' => round($calculation->percentage, 2),
            'period_start' => $calculation->periodStart->toDateString(),
        ]);

        return true;
    }

    private function persistLevel(Budget $budget, BudgetCalculation $calculation, BudgetStatusEnum $level): void
    {
        $periodStart = $calculation->periodStart->toDateString();

        if ($budget->last_notified_level === $level->name
            && $budget->last_notified_period_start?->toDateString() === $periodStart) {
            return;
        }

        $budget->forceFill([
            Budget::last_notified_level => $level->name,
            Budget::last_notified_period_start => $periodStart,
        ])->save();
    }

    /**
     * Bereits benachrichtigte Stufe – beim Periodenwechsel gilt der Zustand als zurückgesetzt.
     */
    private function notifiedSeverity(Budget $budget, BudgetCalculation $calculation): int
    {
        $notifiedPeriodStart = $budget->last_notified_period_start;

        if ($notifiedPeriodStart === null
            || !$notifiedPeriodStart->isSameDay($calculation->periodStart)) {
            return BudgetStatusEnum::OK->severity();
        }

        $level = $budget->last_notified_level !== null
            ? BudgetStatusEnum::tryFrom($budget->last_notified_level)
            : null;

        return $level?->severity() ?? BudgetStatusEnum::OK->severity();
    }
}
