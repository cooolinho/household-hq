<?php

namespace App\Console\Commands;

use App\Jobs\Scheduled\FetchImapDocumentsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:fetch-imap-documents-command')]
#[Description('Command description')]
class FetchImapDocumentsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Dispatching FetchImapDocumentsJob...');
        FetchImapDocumentsJob::dispatch();
        $this->line('Done.');

        return self::SUCCESS;
    }
}
