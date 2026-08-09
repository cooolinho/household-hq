<?php

namespace Tests\Unit;

use App\Mail\FixedCostReminderMail;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostReminder;
use App\Notifications\Financial\FixedCostReminderNotification;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FixedCostReminderTest extends TestCase
{
    public function test_it_returns_human_readable_lead_time_labels(): void
    {
        $this->assertSame('1 Tag vorher', FixedCostReminder::leadTimeLabel(1));
        $this->assertSame('2 Wochen vorher', FixedCostReminder::leadTimeLabel(14));
        $this->assertSame('9 Tage vorher', FixedCostReminder::leadTimeLabel(9));
    }

    public function test_it_returns_human_readable_channel_labels(): void
    {
        $this->assertSame('E-Mail', FixedCostReminder::channelsLabel(true, false));
        $this->assertSame('Benachrichtigung', FixedCostReminder::channelsLabel(false, true));
        $this->assertSame('E-Mail + Benachrichtigung', FixedCostReminder::channelsLabel(true, true));
        $this->assertSame('-', FixedCostReminder::channelsLabel(false, false));
    }

    public function test_it_calculates_the_reminder_date_from_next_booking_date(): void
    {
        $date = FixedCostReminder::calculateReminderDate(CarbonImmutable::parse('2026-08-15'), 7);

        $this->assertNotNull($date);
        $this->assertSame('2026-08-08', $date->toDateString());
        $this->assertSame(
            'Nächste Buchung: 15.08.2026 · Erinnerung bei 7 Tagen Vorlauf: 08.08.2026',
            FixedCostReminder::reminderInfoText(CarbonImmutable::parse('2026-08-15'), 7),
        );
    }

    public function test_notification_via_uses_mail_and_database_when_both_channels_are_enabled(): void
    {
        $fixedCost = $this->makeFixedCost();
        $reminder = $this->makeReminder(sendMail: true, sendNotification: true);
        $notification = new FixedCostReminderNotification($fixedCost, $reminder);

        $channels = $notification->via(new class {
            public string $email = 'user@example.com';
            public string $name = 'Max Mustermann';
        });

        $this->assertSame(['mail', 'database'], $channels);
    }

    private function makeFixedCost(): FixedCost
    {
        $fixedCost = new FixedCost();
        $fixedCost->forceFill([
            FixedCost::name => 'Miete',
            FixedCost::next_booking_date => '2026-08-15',
        ]);

        return $fixedCost;
    }

    private function makeReminder(bool $sendMail, bool $sendNotification): FixedCostReminder
    {
        $reminder = new FixedCostReminder();
        $reminder->forceFill([
            FixedCostReminder::days_before => 7,
            FixedCostReminder::send_mail => $sendMail,
            FixedCostReminder::send_notification => $sendNotification,
            FixedCostReminder::enabled => true,
        ]);

        return $reminder;
    }

    public function test_notification_via_skips_mail_when_no_email_is_available(): void
    {
        $fixedCost = $this->makeFixedCost();
        $reminder = $this->makeReminder(sendMail: true, sendNotification: true);
        $notification = new FixedCostReminderNotification($fixedCost, $reminder);

        $channels = $notification->via(new class {
            public ?string $email = null;
            public string $name = 'Max Mustermann';
        });

        $this->assertSame(['database'], $channels);
    }

    public function test_notification_to_mail_returns_a_mailable(): void
    {
        $fixedCost = $this->makeFixedCost();
        $reminder = $this->makeReminder(sendMail: true, sendNotification: false);
        $notification = new FixedCostReminderNotification($fixedCost, $reminder);

        $mailable = $notification->toMail(new class {
            public string $email = 'user@example.com';
            public string $name = 'Max Mustermann';
        });

        $this->assertInstanceOf(FixedCostReminderMail::class, $mailable);
    }
}

