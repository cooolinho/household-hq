<?php

namespace App\Jobs\Financial;

use App\Jobs\Scheduled\CheckBudgetThresholdsJob;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractTransactionCategorizationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public readonly int $userId,
    )
    {
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }

        $user = $this->getUserForNotification();

        if ($user === null) {
            return;
        }

        Notification::make()
            ->title(sprintf('%s fehlgeschlagen', $this->variantLabel()))
            ->body($exception?->getMessage() ?: 'Der Job konnte nicht abgeschlossen werden.')
            ->danger()
            ->sendToDatabase($user);
    }

    /**
     * @param array{processed: int, categorized: int, skipped: int, removed: int} $results
     */
    protected function notifyCompleted(array $results): void
    {
        // Kategorien haben sich geändert – Budget-Schwellwerte neu bewerten.
        CheckBudgetThresholdsJob::dispatch($this->userId);

        $user = $this->getUserForNotification();

        if ($user === null) {
            return;
        }

        Notification::make()
            ->title(sprintf('%s abgeschlossen', $this->variantLabel()))
            ->body(sprintf(
                'Verarbeitet: %d, kategorisiert: %d, übersprungen: %d, entfernt: %d.',
                $results['processed'],
                $results['categorized'],
                $results['skipped'],
                $results['removed'],
            ))
            ->success()
            ->sendToDatabase($user);
    }

    private function getUserForNotification(): ?User
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            Log::channel('database')->warning('Benachrichtigung zur Transaktionskategorisierung konnte nicht zugestellt werden.', [
                'event' => 'transactions.categorization.notification_skipped',
                'user_id' => $this->userId,
                'job' => static::class,
            ]);
        }

        return $user;
    }

    abstract protected function variantLabel(): string;
}
