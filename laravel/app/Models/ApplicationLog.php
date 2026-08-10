<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property string $channel
 * @property string $level
 * @property string $message
 * @property array<string, mixed>|null $context
 * @property Carbon $occurred_at
 */
class ApplicationLog extends Model
{
    const string TABLE = 'application_logs';

    const string id = 'id';
    const string user_id = 'user_id';
    const string event = 'event';
    const string channel = 'channel';
    const string level = 'level';
    const string message = 'message';
    const string context = 'context';
    const string occurred_at = 'occurred_at';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::event,
        self::channel,
        self::level,
        self::message,
        self::context,
        self::occurred_at,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    protected function casts(): array
    {
        return [
            self::user_id => 'integer',
            self::context => 'array',
            self::occurred_at => 'datetime',
        ];
    }
}

