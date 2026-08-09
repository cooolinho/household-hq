<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class Comment
 *
 * @property int $id
 * @property int $user_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string $message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $user
 * @property Model $commentable
 */
class Comment extends Model
{
    const string TABLE = 'comments';

    // columns
    const string id = 'id';
    const string user_id = 'user_id'; // user who created the comment
    const string commentable_type = 'commentable_type';
    const string commentable_id = 'commentable_id';
    const string message = 'message';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string morph_to_commentable = 'commentable';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::message,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
