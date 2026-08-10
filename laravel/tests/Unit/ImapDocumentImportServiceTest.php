<?php

namespace Tests\Unit;

use App\Services\ImapDocumentImportService;
use Tests\TestCase;

class ImapDocumentImportServiceTest extends TestCase
{
    public function test_it_skips_when_extension_is_not_allowed(): void
    {
        $reason = ImapDocumentImportService::determineSkipReason('xlsx', 'application/vnd.ms-excel', ['pdf', 'txt', 'docx']);

        $this->assertSame('extension_not_allowed', $reason);
    }

    public function test_it_skips_when_mime_is_missing_for_allowed_extension(): void
    {
        $reason = ImapDocumentImportService::determineSkipReason('pdf', null, ['pdf', 'txt', 'docx']);

        $this->assertSame('mime_missing', $reason);
    }

    public function test_it_skips_when_mime_does_not_match_extension_in_strict_mode(): void
    {
        $reason = ImapDocumentImportService::determineSkipReason('pdf', 'text/plain', ['pdf', 'txt', 'docx']);

        $this->assertSame('mime_not_allowed', $reason);
    }

    public function test_it_accepts_when_extension_and_mime_match(): void
    {
        $reason = ImapDocumentImportService::determineSkipReason('invoice.pdf', 'application/pdf', ['pdf', 'txt', 'docx']);

        $this->assertNull($reason);
    }
}

