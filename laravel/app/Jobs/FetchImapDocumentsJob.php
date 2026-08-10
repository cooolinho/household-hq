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

        Log::channel('database')->info('IMAP-Import wurde abgeschlossen.', [
            'event' => 'email.import.summary',
            'accounts' => (int)($result['accounts'] ?? 0),
            'emails' => (int)($result['emails'] ?? 0),
            'documents' => (int)($result['documents'] ?? 0),
            'skipped' => (int)($result['skipped'] ?? 0),
            'failed' => (int)($result['failed'] ?? 0),
        ]);
    }
}

