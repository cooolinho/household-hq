<?php

namespace App\Models\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FixedCostReminder
 *
 * Columns
 * @property int $id
 * @property int $fixed_cost_id
 * @property int $days_before
 * @property bool $send_mail
 * @property bool $send_notification
 * @property bool $enabled
 * @property string|null $last_sent_booking_date
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * Relations
 * @property FixedCost $fixedCost
 */
class FixedCostReminder extends Model
{
    const string TABLE = 'financial_fixed_cost_reminders';

    // columns
    const string id = 'id';
    const string fixed_cost_id = 'fixed_cost_id';
    const string days_before = 'days_before';
    const string send_mail = 'send_mail';
    const string send_notification = 'send_notification';
    const string enabled = 'enabled';
    const string last_sent_booking_date = 'last_sent_booking_date';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // relations
    const string belongs_to_fixed_cost = 'fixedCost';

    protected $table = self::TABLE;

    protected $fillable = [
        self::fixed_cost_id,
        self::days_before,
        self::send_mail,
        self::send_notification,
        self::enabled,
        self::last_sent_booking_date,
    ];

    protected $casts = [
        self::days_before => 'integer',
        self::send_mail => 'boolean',
        self::send_notification => 'boolean',
        self::enabled => 'boolean',
        self::last_sent_booking_date => 'date',
    ];

    public static function leadTimeLabel(int $daysBefore): string
    {
        return self::leadTimeOptions()[$daysBefore] ?? $daysBefore . ' Tage vorher';
    }

    public static function leadTimeOptions(): array
    {
        return [
            1 => '1 Tag vorher',
            2 => '2 Tage vorher',
            3 => '3 Tage vorher',
            7 => '1 Woche vorher',
            14 => '2 Wochen vorher',
            30 => '1 Monat vorher',
        ];
    }

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

    public static function reminderInfoText(mixed $nextBookingDate, int $daysBefore): string
    {
        $bookingDate = self::calculateReminderDate($nextBookingDate, 0);
        $reminderDate = self::calculateReminderDate($nextBookingDate, $daysBefore);

        if ($bookingDate === null || $reminderDate === null) {
            return 'Nächste Buchung: -';
        }

        return sprintf(
            'Nächste Buchung: %s · Erinnerung bei %d Tagen Vorlauf: %s',
            $bookingDate->format('d.m.Y'),
            $daysBefore,
            $reminderDate->format('d.m.Y'),
        );
    }

    public static function calculateReminderDate(mixed $nextBookingDate, int $daysBefore): ?CarbonImmutable
    {
        $date = match (true) {
            $nextBookingDate instanceof CarbonImmutable => $nextBookingDate,
            $nextBookingDate instanceof \Carbon\CarbonInterface => CarbonImmutable::instance($nextBookingDate),
            is_string($nextBookingDate) && $nextBookingDate !== '' => CarbonImmutable::parse($nextBookingDate),
            default => null,
        };

        return $date?->startOfDay()->subDays($daysBefore);
    }

    public function fixedCost(): BelongsTo
    {
        return $this->belongsTo(FixedCost::class, self::fixed_cost_id);
    }
}

