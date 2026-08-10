<?php

namespace App\Jobs;

use App\Services\ImapDocumentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchImapDocumentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(ImapDocumentImportService $service): void
    {
        $result = $service->importAllActiveAccounts();

        Log::info('[FetchImapDocumentsJob] Import summary', $result);
    }
}

