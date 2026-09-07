<?php

namespace App\Models;

use App\Models\Enums\ReminderOffsetUnitEnum;
use App\Models\Enums\ReminderRecurrenceEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ReminderSchedule
 *
 * Ein Intervall eines Reminders. Bei einem an einen Datensatz gebundenen Reminder
 * beschreibt es einen Vorlauf (offset_value/offset_unit) vor dem Ankerdatum. Bei einem
 * freien Reminder beschreibt es eine Wiederholungsregel (recurrence/recurrence_value).
 *
 * Columns
 * @property int $id
 * @property int $reminder_id
 * @property int|null $offset_value
 * @property string|null $offset_unit
 * @property string|null $recurrence
 * @property int|null $recurrence_value
 * @property Carbon|null $start_date
 * @property string|null $run_at_time
 * @property Carbon|null $next_due_at
 * @property Carbon|null $last_sent_at
 * @property Carbon|null $last_sent_for
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Relations
 * @property Reminder $reminder
 */
class ReminderSchedule extends Model
{
    const string TABLE = 'reminder_schedules';

    // columns
    const string id = 'id';
    const string reminder_id = 'reminder_id';
    const string offset_value = 'offset_value';
    const string offset_unit = 'offset_unit';
    const string recurrence = 'recurrence';
    const string recurrence_value = 'recurrence_value';
    const string start_date = 'start_date';
    const string run_at_time = 'run_at_time';
    const string next_due_at = 'next_due_at';
    const string last_sent_at = 'last_sent_at';
    const string last_sent_for = 'last_sent_for';
    const string enabled = 'enabled';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_reminder = 'reminder';

    protected $table = self::TABLE;

    protected $fillable = [
        self::reminder_id,
        self::offset_value,
        self::offset_unit,
        self::recurrence,
        self::recurrence_value,
        self::start_date,
        self::run_at_time,
        self::next_due_at,
        self::last_sent_at,
        self::last_sent_for,
        self::enabled,
    ];

    protected $casts = [
        self::offset_value => 'integer',
        self::recurrence_value => 'integer',
        // immutable_* casts so calculator code (Carbon\CarbonImmutable throughout) can operate
        // on these attributes without type-juggling between Carbon and CarbonImmutable.
        self::start_date => 'immutable_date',
        self::next_due_at => 'immutable_datetime',
        self::last_sent_at => 'immutable_datetime',
        self::last_sent_for => 'immutable_datetime',
        self::enabled => 'boolean',
    ];

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class, self::reminder_id);
    }

    public function label(): string
    {
        if ($this->isRecurring()) {
            $recurrence = ReminderRecurrenceEnum::tryFrom((string)$this->{self::recurrence});

            if ($recurrence === null) {
                return '-';
            }

            return match ($recurrence) {
                ReminderRecurrenceEnum::EVERY_N_DAYS => sprintf('Alle %d Tage', (int)$this->{self::recurrence_value}),
                ReminderRecurrenceEnum::EVERY_N_WEEKS => sprintf('Alle %d Wochen', (int)$this->{self::recurrence_value}),
                ReminderRecurrenceEnum::EVERY_N_MONTHS => sprintf('Alle %d Monate', (int)$this->{self::recurrence_value}),
                ReminderRecurrenceEnum::MONTHLY_ON_DAY => sprintf('Am %d. eines jeden Monats', (int)$this->{self::recurrence_value}),
                ReminderRecurrenceEnum::ONCE => sprintf('Am %s', $this->{self::start_date}?->format('d.m.Y') ?? '-'),
                default => $recurrence->label(),
            };
        }

        $unit = ReminderOffsetUnitEnum::tryFrom((string)$this->{self::offset_unit});

        if ($unit === null || $this->{self::offset_value} === null) {
            return '-';
        }

        return sprintf('%d %s vorher', (int)$this->{self::offset_value}, $unit->label());
    }

    public function isRecurring(): bool
    {
        return $this->{self::recurrence} !== null;
    }
}
