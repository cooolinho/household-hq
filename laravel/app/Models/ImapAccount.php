<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $encryption
 * @property string $username
 * @property string $password
 * @property string $inbox_folder
 * @property string $processed_folder
 * @property bool $mark_as_read
 * @property bool $is_active
 * @property array<int, string>|null $allowed_extensions
 * @property array<int, string>|null $blacklisted_senders
 * @property array<int, string>|null $blacklisted_subject_keywords
 * @property Carbon|null $last_run_at
 * @property string|null $last_error
 */
class ImapAccount extends Model
{
    const string TABLE = 'imap_accounts';

    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string host = 'host';
    const string port = 'port';
    const string encryption = 'encryption';
    const string username = 'username';
    const string password = 'password';
    const string inbox_folder = 'inbox_folder';
    const string processed_folder = 'processed_folder';
    const string mark_as_read = 'mark_as_read';
    const string is_active = 'is_active';
    const string allowed_extensions = 'allowed_extensions';
    const string blacklisted_senders = 'blacklisted_senders';
    const string blacklisted_subject_keywords = 'blacklisted_subject_keywords';
    const string last_run_at = 'last_run_at';
    const string last_error = 'last_error';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';
    const string has_many_imported_emails = 'importedEmails';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::host,
        self::port,
        self::encryption,
        self::username,
        self::password,
        self::inbox_folder,
        self::processed_folder,
        self::mark_as_read,
        self::is_active,
        self::allowed_extensions,
        self::blacklisted_senders,
        self::blacklisted_subject_keywords,
        self::last_run_at,
        self::last_error,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function importedEmails(): HasMany
    {
        return $this->hasMany(ImportedEmail::class, ImportedEmail::imap_account_id);
    }

    public function createFolderPath(?string $subFolder = null): string
    {
        return sprintf('%s.%s', $this->inbox_folder, $subFolder ?? '');
    }

    protected function casts(): array
    {
        return [
            self::port => 'integer',
            self::mark_as_read => 'boolean',
            self::is_active => 'boolean',
            self::allowed_extensions => 'array',
            self::blacklisted_senders => 'array',
            self::blacklisted_subject_keywords => 'array',
            self::last_run_at => 'datetime',
            self::password => 'encrypted',
        ];
    }
}

