<?php

namespace App\Jobs\Financial;

use App\Services\TransactionCategorizationService;

class RecategorizeCategorizedTransactionsJob extends AbstractTransactionCategorizationJob
{
    public function handle(TransactionCategorizationService $service): void
    {
        $this->notifyCompleted($service->recategorizeCategorized($this->userId));
    }

    protected function variantLabel(): string
    {
        return 'Die erneute Kategorisierung bereits kategorisierter Transaktionen';
    }
}
