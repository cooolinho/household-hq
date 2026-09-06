<?php

namespace App\Jobs\Scheduled;

use App\Models\Financial\Budget;
use App\Models\User;
use App\Services\Budget\BudgetNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckBudgetThresholdsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly ?int $userId = null,
    )
    {
    }

    public static function description(): string
    {
        return 'Budget-Schwellwerte prüfen und Benachrichtigungen versenden';
    }

    public function handle(BudgetNotificationService $service): void
    {
        try {
            $query = User::query()
                ->whereHas(User::has_many_budgets, static fn($budgets) => $budgets->where(Budget::active, true));

            if ($this->userId !== null) {
                $query->where(User::id, $this->userId);
            }

            $notified = 0;
            $checkedUsers = 0;

            $query->each(function (User $user) use ($service, &$notified, &$checkedUsers): void {
                $notified += $service->checkForUser((int)$user->getKey());
                $checkedUsers++;
            });

            Log::channel('database')->info('Budget-Schwellwerte wurden geprüft.', [
                'event' => 'budget.thresholds.checked',
                'results' => [
                    'users_checked' => $checkedUsers,
                    'notifications_sent' => $notified,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::channel('database')->error('Prüfung der Budget-Schwellwerte fehlgeschlagen.', [
                'event' => 'budget.thresholds.failed',
                'user_id' => $this->userId,
                'message' => $exception->getMessage(),
            ]);

            $this->fail($exception);
        }
    }
}
