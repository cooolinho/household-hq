<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class Document
 *
 * @property int $id
 *
 * // core relation to user
 * @property int $user_id
 *
 * // polymorphic relation
 * @property string|null $documentable_type
 * @property int|null $documentable_id
 *
 * // document data
 * @property string|null $type        // z.B. contract, invoice, sonstiges
 * @property string $path             // storage path oder URL
 * @property string|null $filename
 * @property string|null $description
 * @property int $sort
 *
 * // file metadata (added)
 * @property int|null $file_size      // size in bytes
 * @property string|null $mime_type
 *
 * // timestamps
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * // relation methods
 * @property User $user
 * @property Model|null $documentable
 */
class Document extends Model
{
    const string TABLE = 'documents';
    const string STORAGE_DISK = 'documents';

    const string id = 'id';

    // core relation to user
    const string user_id = 'user_id';

    // polymorphic relation
    const string documentable_type = 'documentable_type';
    const string documentable_id = 'documentable_id';
    const string morph_to_documentable = 'documentable';

    // document data
    const string type = 'type';
    const string path = 'path';
    const string filename = 'filename';
    const string description = 'description';
    const string sort = 'sort';

    // file metadata (added)
    const string file_size = 'file_size';
    const string mime_type = 'mime_type';

    // timestamps
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    // relation methods
    const string belongs_to_user = 'user';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::documentable_type,
        self::documentable_id,
        self::type,        // z.B. contract, invoice, sonstiges
        self::path,        // storage path oder URL
        self::filename,
        self::description,
        self::sort,
        self::file_size,   // size in bytes
        self::mime_type,
    ];

    protected $casts = [
        self::sort => 'integer',
        self::file_size => 'integer',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Document $document) {
            // on creating set file_size and mime_type from storage if not set
            if (empty($document->{self::file_size}) || empty($document->{self::mime_type})) {
                $document->setMetaData();
            }

            // set filename from path if not set
            if (empty($document->{self::filename}) && !empty($document->{self::path})) {
                $document->{self::filename} = basename($document->{self::path});
            }
        });
    }

    // Booted: listen for created event to populate file metadata

    /**
     * @return void
     */
    private function setMetaData(): void
    {
        $path = $this->{self::path} ?? null;
        if (!empty($path) || $path === '0') {
            $diskName = self::STORAGE_DISK;
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($path)) {
                    if (empty($this->{self::file_size})) {
                        $this->{self::file_size} = $disk->size($path);
                    }
                    if (empty($this->{self::mime_type})) {
                        try {
                            $this->{self::mime_type} = $disk->mimeType($path);
                        } catch (Exception $e) {
                            // ignore
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore and fallback
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function documentable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(self::morph_to_documentable);
    }
}
