<?php

namespace App\Jobs\Scheduled;

use App\Models\User;
use App\Services\TransactionStatisticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshTransactionStatisticsJob implements ShouldQueue
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
        return 'Transaktionsstatistiken im Cache aktualisieren';
    }

    public function handle(TransactionStatisticsService $service): void
    {
        $refreshed = 0;

        $query = $this->userId !== null
            ? User::query()->where(User::id, $this->userId)
            : User::query();

        $query->each(function (User $user) use ($service, &$refreshed): void {
            // Use EUR as the default currency; the service normalises it
            $service->refreshCache($user->id, 'EUR');
            $refreshed++;
        });

        Log::channel('database')->info('Transaktionsstatistiken wurden im Cache aktualisiert.', [
            'event' => 'transactions.statistics.cache_refreshed',
            'results' => ['users_refreshed' => $refreshed],
        ]);
    }
}
