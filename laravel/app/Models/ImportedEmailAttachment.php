<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $imported_email_id
 * @property int|null $document_id
 * @property string|null $filename
 * @property string|null $extension
 * @property string|null $mime_type
 * @property int|null $size
 * @property string $status
 * @property string|null $skip_reason
 * @property string|null $error_message
 */
class ImportedEmailAttachment extends Model
{
    const string TABLE = 'imported_email_attachments';

    const string id = 'id';
    const string user_id = 'user_id';
    const string imported_email_id = 'imported_email_id';
    const string document_id = 'document_id';
    const string filename = 'filename';
    const string extension = 'extension';
    const string mime_type = 'mime_type';
    const string size = 'size';
    const string status = 'status';
    const string skip_reason = 'skip_reason';
    const string error_message = 'error_message';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';
    const string belongs_to_imported_email = 'importedEmail';
    const string belongs_to_document = 'document';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::imported_email_id,
        self::document_id,
        self::filename,
        self::extension,
        self::mime_type,
        self::size,
        self::status,
        self::skip_reason,
        self::error_message,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function importedEmail(): BelongsTo
    {
        return $this->belongsTo(ImportedEmail::class, self::imported_email_id);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, self::document_id);
    }

    protected function casts(): array
    {
        return [
            self::size => 'integer',
        ];
    }
}

