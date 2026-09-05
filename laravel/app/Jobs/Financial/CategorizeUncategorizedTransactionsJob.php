<?php

namespace App\Jobs\Financial;

use App\Services\TransactionCategorizationService;

class CategorizeUncategorizedTransactionsJob extends AbstractTransactionCategorizationJob
{
    public function handle(TransactionCategorizationService $service): void
    {
        $this->notifyCompleted($service->categorizeUncategorized($this->userId));
    }

    protected function variantLabel(): string
    {
        return 'Die Kategorisierung noch nicht kategorisierter Transaktionen';
    }
}
