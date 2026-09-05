<?php

namespace App\Jobs\Financial;

use App\Services\TransactionCategorizationService;

class RecategorizeAllTransactionsJob extends AbstractTransactionCategorizationJob
{
    public function handle(TransactionCategorizationService $service): void
    {
        $this->notifyCompleted($service->recategorizeAll($this->userId));
    }

    protected function variantLabel(): string
    {
        return 'Die vollständige Neukategorisierung aller Transaktionen';
    }
}
