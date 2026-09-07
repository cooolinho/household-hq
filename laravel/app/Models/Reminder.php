<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class Reminder
 *
 * Ein Reminder erinnert entweder an eine Datums-Property eines beliebigen Models
 * (z. B. FixedCost::next_booking_date) oder läuft frei nach einer Wiederholungsregel
 * mit eigenem Text. Die eigentlichen zeitlichen Regeln liegen in den zugehörigen
 * ReminderSchedule-Datensätzen (n Intervalle pro Reminder).
 *
 * Columns
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $remindable_type
 * @property int|null $remindable_id
 * @property string|null $date_property
 * @property string|null $message
 * @property bool $send_mail
 * @property bool $send_notification
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property User $user
 * @property Model|null $remindable
 * @property \Illuminate\Database\Eloquent\Collection|ReminderSchedule[] $schedules
 */
class Reminder extends Model
{
    const string TABLE = 'reminders';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string name = 'name';
    const string remindable_type = 'remindable_type';
    const string remindable_id = 'remindable_id';
    const string date_property = 'date_property';
    const string message = 'message';
    const string send_mail = 'send_mail';
    const string send_notification = 'send_notification';
    const string enabled = 'enabled';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_user = 'user';
    const string morph_to_remindable = 'remindable';
    const string has_many_schedules = 'schedules';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::name,
        self::remindable_type,
        self::remindable_id,
        self::date_property,
        self::message,
        self::send_mail,
        self::send_notification,
        self::enabled,
    ];

    protected $casts = [
        self::send_mail => 'boolean',
        self::send_notification => 'boolean',
        self::enabled => 'boolean',
    ];

    public static function channelsLabel(bool $sendMail, bool $sendNotification): string
    {
        $channels = [];

        if ($sendMail) {
            $channels[] = 'E-Mail';
        }

        if ($sendNotification) {
            $channels[] = 'Benachrichtigung';
        }

        return $channels === [] ? '-' : implode(' + ', $channels);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReminderSchedule::class, ReminderSchedule::reminder_id);
    }

    /**
     * Ob dieser Reminder an einen Datensatz gebunden ist (statt frei nach Wiederholungsregel zu laufen).
     */
    public function isAnchored(): bool
    {
        return $this->{self::remindable_type} !== null && $this->{self::date_property} !== null;
    }
}
