<?php

namespace App\Jobs\Scheduled;

use App\Services\ImapDocumentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchImapDocumentsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public static function description(): string
    {
        return 'IMAP-Dokumente aus allen aktiven Konten importieren';
    }

    public function handle(ImapDocumentImportService $service): void
    {
        $result = $service->importAllActiveAccounts();

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

