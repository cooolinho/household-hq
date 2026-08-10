<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Enums\ImportedEmailAttachmentStatusEnum;
use App\Models\ImapAccount;
use App\Models\ImportedEmail;
use App\Models\ImportedEmailAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImapDocumentImportService
{
    /**
     * @var array<string, array<int, string>>
     */
    private const array ALLOWED_MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public function importAllActiveAccounts(): array
    {
        $summary = [
            'accounts' => 0,
            'emails' => 0,
            'documents' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        ImapAccount::query()
            ->where(ImapAccount::is_active, true)
            ->orderBy(ImapAccount::id)
            ->get()
            ->each(function (ImapAccount $account) use (&$summary): void {
                $result = $this->importAccount($account);
                $summary['accounts']++;
                $summary['emails'] += (int)($result['emails'] ?? 0);
                $summary['documents'] += (int)($result['documents'] ?? 0);
                $summary['skipped'] += (int)($result['skipped'] ?? 0);
                $summary['failed'] += (int)($result['failed'] ?? 0);
            });

        return $summary;
    }

    public function importAccount(ImapAccount $account): array
    {
        $result = [
            'emails' => 0,
            'documents' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if (!function_exists('imap_open')) {
            $error = 'PHP extension "imap" is not available.';
            $account->update([
                ImapAccount::last_run_at => now(),
                ImapAccount::last_error => $error,
            ]);

            Log::warning('[ImapDocumentImportService] IMAP extension missing', [
                'accountId' => $account->id,
                'userId' => $account->user_id,
            ]);

            return $result;
        }

        $stream = null;

        try {
            $stream = @imap_open(
                $this->buildMailboxString($account, $account->inbox_folder ?: 'INBOX'),
                $account->username,
                $account->password,
                OP_SILENT,
                1
            );

            if (!$stream) {
                throw new \RuntimeException((string)imap_last_error());
            }

            $searchFlags = defined('SE_UID') ? SE_UID : 0;
            $uids = imap_search($stream, 'ALL', $searchFlags) ?: [];

            foreach ($uids as $uid) {
                $uid = (int)$uid;

                if ($uid <= 0) {
                    continue;
                }

                $messageId = $this->resolveMessageId($stream, $uid);

                if ($this->hasEmailBeenImported($account, $uid, $messageId)) {
                    continue;
                }

                $email = $this->createImportedEmailRecord($stream, $account, $uid);
                $result['emails']++;

                $meta = $this->extractAttachmentMeta($stream, $uid);
                $warningMessages = [];
                $warningCount = 0;

                foreach ($meta as $attachment) {
                    $skipReason = self::determineSkipReason(
                        $attachment['extension'],
                        $attachment['mime_type'],
                        (array)($account->allowed_extensions ?: ['pdf', 'txt', 'docx'])
                    );

                    if ($skipReason !== null) {
                        ImportedEmailAttachment::query()->create([
                            ImportedEmailAttachment::user_id => $account->user_id,
                            ImportedEmailAttachment::imported_email_id => $email->id,
                            ImportedEmailAttachment::filename => $attachment['filename'],
                            ImportedEmailAttachment::extension => $attachment['extension'],
                            ImportedEmailAttachment::mime_type => $attachment['mime_type'],
                            ImportedEmailAttachment::size => $attachment['size'],
                            ImportedEmailAttachment::status => ImportedEmailAttachmentStatusEnum::SKIPPED->name,
                            ImportedEmailAttachment::skip_reason => $skipReason,
                        ]);

                        $result['skipped']++;
                        $warningCount++;
                        $warningMessages[] = sprintf('Anhang "%s" übersprungen (%s).', (string)$attachment['filename'], $skipReason);
                        continue;
                    }

                    try {
                        $document = $this->storeAttachmentAsDocument($stream, $account, $uid, $attachment, $email);

                        ImportedEmailAttachment::query()->create([
                            ImportedEmailAttachment::user_id => $account->user_id,
                            ImportedEmailAttachment::imported_email_id => $email->id,
                            ImportedEmailAttachment::document_id => $document->id,
                            ImportedEmailAttachment::filename => $attachment['filename'],
                            ImportedEmailAttachment::extension => $attachment['extension'],
                            ImportedEmailAttachment::mime_type => $attachment['mime_type'],
                            ImportedEmailAttachment::size => $attachment['size'],
                            ImportedEmailAttachment::status => ImportedEmailAttachmentStatusEnum::IMPORTED->name,
                        ]);

                        $result['documents']++;
                    } catch (Throwable $e) {
                        ImportedEmailAttachment::query()->create([
                            ImportedEmailAttachment::user_id => $account->user_id,
                            ImportedEmailAttachment::imported_email_id => $email->id,
                            ImportedEmailAttachment::filename => $attachment['filename'],
                            ImportedEmailAttachment::extension => $attachment['extension'],
                            ImportedEmailAttachment::mime_type => $attachment['mime_type'],
                            ImportedEmailAttachment::size => $attachment['size'],
                            ImportedEmailAttachment::status => ImportedEmailAttachmentStatusEnum::FAILED->name,
                            ImportedEmailAttachment::error_message => $e->getMessage(),
                        ]);

                        $result['failed']++;
                        $warningCount++;
                        $warningMessages[] = sprintf('Anhang "%s" fehlgeschlagen (%s).', (string)$attachment['filename'], $e->getMessage());
                    }
                }

                $markedAsRead = false;
                if ($account->mark_as_read) {
                    $markFlags = defined('ST_UID') ? ST_UID : 0;
                    $markedAsRead = (bool)@imap_setflag_full($stream, (string)$uid, '\\Seen', $markFlags);

                    if (!$markedAsRead) {
                        $warningCount++;
                        $warningMessages[] = 'E-Mail konnte nicht als gelesen markiert werden.';
                    }
                }

                [$movedToProcessed, $moveWarning] = $this->moveMessageToProcessedFolder($stream, $account, $uid);
                if (!$movedToProcessed) {
                    $warningCount++;
                    $warningMessages[] = $moveWarning ?: 'E-Mail konnte nicht in den processed-Ordner verschoben werden.';
                }

                $email->update([
                    ImportedEmail::marked_as_read => $markedAsRead,
                    ImportedEmail::moved_to_processed => $movedToProcessed,
                    ImportedEmail::warning_count => $warningCount,
                    ImportedEmail::warning_summary => $warningCount > 0 ? implode(' ', $warningMessages) : null,
                    ImportedEmail::processed_at => now(),
                ]);
            }

            $account->update([
                ImapAccount::last_run_at => now(),
                ImapAccount::last_error => null,
            ]);
        } catch (Throwable $e) {
            $account->update([
                ImapAccount::last_run_at => now(),
                ImapAccount::last_error => $e->getMessage(),
            ]);

            Log::error('[ImapDocumentImportService] Account import failed', [
                'accountId' => $account->id,
                'userId' => $account->user_id,
                'message' => $e->getMessage(),
            ]);
        } finally {
            if (is_resource($stream)) {
                imap_close($stream);
            }
        }

        return $result;
    }

    private function buildMailboxString(ImapAccount $account, string $folder): string
    {
        $encryption = strtolower(trim((string)$account->encryption));

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        return sprintf('{%s:%d%s}%s', $account->host, $account->port, $flags, $folder);
    }

    private function resolveMessageId($stream, int $uid): ?string
    {
        $fetchFlags = defined('FT_UID') ? FT_UID : 0;
        $overview = imap_fetch_overview($stream, (string)$uid, $fetchFlags);
        $data = is_array($overview) && isset($overview[0]) ? $overview[0] : null;

        $messageId = isset($data->message_id) ? trim((string)$data->message_id, '<>') : null;

        return $messageId !== '' ? $messageId : null;
    }

    private function hasEmailBeenImported(ImapAccount $account, int $uid, ?string $messageId): bool
    {
        return ImportedEmail::query()
            ->where(ImportedEmail::imap_account_id, $account->id)
            ->where(function ($query) use ($uid, $messageId): void {
                $query->where(ImportedEmail::uid, $uid);

                if (!empty($messageId)) {
                    $query->orWhere(ImportedEmail::message_id, $messageId);
                }
            })
            ->exists();
    }

    private function createImportedEmailRecord($stream, ImapAccount $account, int $uid): ImportedEmail
    {
        $fetchFlags = defined('FT_UID') ? FT_UID : 0;
        $overview = imap_fetch_overview($stream, (string)$uid, $fetchFlags);
        $data = is_array($overview) && isset($overview[0]) ? $overview[0] : null;

        $messageId = isset($data->message_id) ? trim((string)$data->message_id, '<>') : null;
        $subject = isset($data->subject) ? imap_utf8((string)$data->subject) : null;
        $fromRaw = isset($data->from) ? imap_utf8((string)$data->from) : null;
        $receivedAt = null;
        if (!empty($data?->date)) {
            try {
                $receivedAt = Carbon::parse((string)$data->date);
            } catch (Throwable) {
                $receivedAt = null;
            }
        }

        [$fromName, $fromEmail] = $this->parseFromAddress($fromRaw);

        $headers = @imap_fetchheader($stream, (string)$uid, $fetchFlags) ?: null;

        return ImportedEmail::query()->create([
            ImportedEmail::user_id => $account->user_id,
            ImportedEmail::imap_account_id => $account->id,
            ImportedEmail::message_id => $messageId,
            ImportedEmail::uid => $uid,
            ImportedEmail::subject => $subject,
            ImportedEmail::from_name => $fromName,
            ImportedEmail::from_email => $fromEmail,
            ImportedEmail::received_at => $receivedAt,
            ImportedEmail::mailbox_folder => $account->inbox_folder ?: 'INBOX',
            ImportedEmail::raw_headers => $headers,
        ]);
    }

    private function parseFromAddress(?string $fromRaw): array
    {
        $raw = trim((string)$fromRaw);
        if ($raw === '') {
            return [null, null];
        }

        if (preg_match('/^(.*)<([^>]+)>$/', $raw, $matches) === 1) {
            return [trim((string)$matches[1], " \t\n\r\0\x0B\""), trim((string)$matches[2])];
        }

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return [null, $raw];
        }

        return [$raw, null];
    }

    /**
     * @return array<int, array{part_number: string, filename: string|null, extension: string|null, mime_type: string|null, size: int|null, encoding: int}>
     */
    private function extractAttachmentMeta($stream, int $uid): array
    {
        $fetchFlags = defined('FT_UID') ? FT_UID : 0;
        $structure = @imap_fetchstructure($stream, (string)$uid, $fetchFlags);
        if (!is_object($structure)) {
            return [];
        }

        $attachments = [];
        $this->walkParts($structure, '', $attachments);

        return $attachments;
    }

    /**
     * @param array<int, array{part_number: string, filename: string|null, extension: string|null, mime_type: string|null, size: int|null, encoding: int}> $attachments
     */
    private function walkParts(object $part, string $prefix, array &$attachments): void
    {
        $parts = is_array($part->parts ?? null) ? $part->parts : null;

        if ($parts !== null) {
            foreach ($parts as $index => $subPart) {
                $partNumber = $prefix === '' ? (string)($index + 1) : $prefix . '.' . ($index + 1);
                $this->walkParts($subPart, $partNumber, $attachments);
            }

            return;
        }

        $disposition = strtolower((string)($part->disposition ?? ''));
        $filename = $this->extractFilename($part);
        $isAttachment = in_array($disposition, ['attachment', 'inline'], true) && !empty($filename);

        if (!$isAttachment) {
            return;
        }

        $mimeType = $this->resolveMimeType($part);
        $extension = self::normalizeExtension($filename);

        $attachments[] = [
            'part_number' => $prefix === '' ? '1' : $prefix,
            'filename' => $filename,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => isset($part->bytes) ? (int)$part->bytes : null,
            'encoding' => isset($part->encoding) ? (int)$part->encoding : 0,
        ];
    }

    private function extractFilename(object $part): ?string
    {
        $parameters = array_merge(
            is_array($part->parameters ?? null) ? $part->parameters : [],
            is_array($part->dparameters ?? null) ? $part->dparameters : []
        );

        foreach ($parameters as $parameter) {
            $attribute = strtolower((string)($parameter->attribute ?? ''));
            if (in_array($attribute, ['name', 'filename'], true)) {
                $value = trim((string)($parameter->value ?? ''));
                return $value !== '' ? imap_utf8($value) : null;
            }
        }

        return null;
    }

    private function resolveMimeType(object $part): ?string
    {
        $types = [
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'other',
        ];

        $typeKey = isset($part->type) ? (int)$part->type : 7;
        $major = $types[$typeKey] ?? 'other';
        $sub = strtolower((string)($part->subtype ?? 'octet-stream'));

        return $major . '/' . $sub;
    }

    public static function normalizeExtension(?string $filename): ?string
    {
        $value = strtolower(trim((string)$filename));
        if ($value === '') {
            return null;
        }

        $value = ltrim($value, '.');
        if (str_contains($value, '.')) {
            $value = (string)pathinfo($value, PATHINFO_EXTENSION);
        }

        return $value === '' ? null : $value;
    }

    public static function determineSkipReason(?string $extension, ?string $mimeType, array $allowedExtensions): ?string
    {
        $normalizedExtension = self::normalizeExtension($extension);
        $allowedExtensions = array_map(static fn($ext): string => strtolower((string)$ext), $allowedExtensions);

        if (empty($normalizedExtension) || !in_array($normalizedExtension, $allowedExtensions, true)) {
            return 'extension_not_allowed';
        }

        $expectedMimes = self::ALLOWED_MIME_BY_EXTENSION[$normalizedExtension] ?? [];
        if (empty($expectedMimes)) {
            return 'extension_not_allowed';
        }

        $normalizedMime = strtolower(trim((string)$mimeType));
        if ($normalizedMime === '') {
            return 'mime_missing';
        }

        if (!in_array($normalizedMime, $expectedMimes, true)) {
            return 'mime_not_allowed';
        }

        return null;
    }

    /**
     * @param array{part_number: string, filename: string|null, extension: string|null, mime_type: string|null, size: int|null, encoding: int} $attachment
     */
    private function storeAttachmentAsDocument($stream, ImapAccount $account, int $uid, array $attachment, ImportedEmail $email): Document
    {
        $fetchFlags = defined('FT_UID') ? FT_UID : 0;
        $rawContent = imap_fetchbody($stream, (string)$uid, $attachment['part_number'], $fetchFlags);

        if (!is_string($rawContent)) {
            throw new \RuntimeException('Attachment content could not be loaded.');
        }

        $content = $this->decodeAttachmentContent($rawContent, $attachment['encoding']);
        $filename = $attachment['filename'] ?: 'attachment_' . $uid;

        $safeFilename = (string)Str::of($filename)->replaceMatches('/[^A-Za-z0-9._-]/', '_');
        $path = sprintf('email-import/%d/%d/%d_%s', $account->user_id, $account->id, $uid, $safeFilename);

        Storage::disk(Document::STORAGE_DISK)->put($path, $content);

        return Document::query()->create([
            Document::user_id => $account->user_id,
            Document::type => 'email_attachment',
            Document::path => $path,
            Document::filename => $filename,
            Document::description => sprintf(
                'Importiert via E-Mail: %s (%s)',
                (string)($email->subject ?: 'ohne Betreff'),
                (string)($email->from_email ?: 'unbekannt')
            ),
            Document::sort => 0,
            Document::mime_type => $attachment['mime_type'],
            Document::file_size => strlen($content),
        ]);
    }

    private function decodeAttachmentContent(string $rawContent, int $encoding): string
    {
        return match ($encoding) {
            3 => $this->decodeBase64($rawContent),
            4 => quoted_printable_decode($rawContent),
            default => $rawContent,
        };
    }

    private function decodeBase64(string $rawContent): string
    {
        $decoded = base64_decode($rawContent, true);

        if ($decoded !== false) {
            return $decoded;
        }

        throw new \RuntimeException('Attachment content could not be base64 decoded.');
    }

    private function moveMessageToProcessedFolder($stream, ImapAccount $account, int $uid): array
    {
        $processedFolder = trim((string)$account->processed_folder);
        if ($processedFolder === '') {
            return [false, 'processed_folder_empty'];
        }

        $processedFolder = $account->createFolderPath($processedFolder);
        $moveFlags = defined('CP_UID') ? CP_UID : 0;
        $moved = (bool)@imap_mail_move($stream, (string)$uid, $processedFolder, $moveFlags);

        if ($moved) {
            @imap_expunge($stream);
            return [true, null];
        }

        $created = $this->createMailboxIfMissing($stream, $account, $processedFolder);
        if ($created) {
            $moved = (bool)@imap_mail_move($stream, (string)$uid, $processedFolder, $moveFlags);
            if ($moved) {
                @imap_expunge($stream);
                return [true, null];
            }
        }

        return [false, (string)(imap_last_error() ?: 'move_failed')];
    }

    private function createMailboxIfMissing($stream, ImapAccount $account, string $folder): bool
    {
        $mailbox = $this->buildMailboxString($account, $folder);

        return (bool)@imap_createmailbox($stream, imap_utf7_encode($mailbox));
    }
}

