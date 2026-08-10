<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $imap_account_id
 * @property string|null $message_id
 * @property int|null $uid
 * @property string|null $subject
 * @property string|null $from_email
 * @property string|null $from_name
 * @property Carbon|null $received_at
 * @property string $mailbox_folder
 * @property bool $marked_as_read
 * @property bool $moved_to_processed
 * @property int $warning_count
 * @property string|null $warning_summary
 * @property Carbon|null $processed_at
 */
class ImportedEmail extends Model
{
    const string TABLE = 'imported_emails';

    const string id = 'id';
    const string user_id = 'user_id';
    const string imap_account_id = 'imap_account_id';
    const string message_id = 'message_id';
    const string uid = 'uid';
    const string subject = 'subject';
    const string from_email = 'from_email';
    const string from_name = 'from_name';
    const string received_at = 'received_at';
    const string mailbox_folder = 'mailbox_folder';
    const string raw_headers = 'raw_headers';
    const string marked_as_read = 'marked_as_read';
    const string moved_to_processed = 'moved_to_processed';
    const string warning_count = 'warning_count';
    const string warning_summary = 'warning_summary';
    const string processed_at = 'processed_at';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';
    const string belongs_to_imap_account = 'imapAccount';
    const string has_many_attachments = 'attachments';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::imap_account_id,
        self::message_id,
        self::uid,
        self::subject,
        self::from_email,
        self::from_name,
        self::received_at,
        self::mailbox_folder,
        self::raw_headers,
        self::marked_as_read,
        self::moved_to_processed,
        self::warning_count,
        self::warning_summary,
        self::processed_at,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function imapAccount(): BelongsTo
    {
        return $this->belongsTo(ImapAccount::class, self::imap_account_id);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ImportedEmailAttachment::class, ImportedEmailAttachment::imported_email_id);
    }

    protected function casts(): array
    {
        return [
            self::uid => 'integer',
            self::marked_as_read => 'boolean',
            self::moved_to_processed => 'boolean',
            self::warning_count => 'integer',
            self::received_at => 'datetime',
            self::processed_at => 'datetime',
        ];
    }
}

