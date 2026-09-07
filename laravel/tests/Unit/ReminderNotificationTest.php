<?php

namespace Tests\Unit;

use App\Mail\ReminderMail;
use App\Models\Financial\FixedCost;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Notifications\ReminderNotification;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ReminderNotificationTest extends TestCase
{
    public function test_via_uses_mail_and_database_when_both_channels_are_enabled(): void
    {
        $notification = new ReminderNotification($this->makeAnchoredReminder(), $this->makeSchedule(), CarbonImmutable::parse('2026-08-15'));

        $channels = $notification->via(new class {
            public string $email = 'user@example.com';
            public string $name = 'Max Mustermann';
        });

        $this->assertSame(['mail', 'database'], $channels);
    }

    private function makeAnchoredReminder(): Reminder
    {
        $fixedCost = new FixedCost();
        $fixedCost->forceFill([
            FixedCost::name => 'Miete',
            FixedCost::next_booking_date => '2026-08-22',
        ]);

        $reminder = new Reminder();
        $reminder->forceFill([
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);
        $reminder->setRelation(Reminder::morph_to_remindable, $fixedCost);

        return $reminder;
    }

    private function makeSchedule(): ReminderSchedule
    {
        $schedule = new ReminderSchedule();
        $schedule->forceFill([
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
        ]);

        return $schedule;
    }

    public function test_via_skips_mail_when_no_email_is_available(): void
    {
        $notification = new ReminderNotification($this->makeAnchoredReminder(), $this->makeSchedule(), CarbonImmutable::parse('2026-08-15'));

        $channels = $notification->via(new class {
            public ?string $email = null;
            public string $name = 'Max Mustermann';
        });

        $this->assertSame(['database'], $channels);
    }

    public function test_to_mail_returns_a_mailable_with_the_target_title_in_the_body(): void
    {
        $notification = new ReminderNotification($this->makeAnchoredReminder(), $this->makeSchedule(), CarbonImmutable::parse('2026-08-15'));

        $mailable = $notification->toMail(new class {
            public string $email = 'user@example.com';
            public string $name = 'Max Mustermann';
        });

        $this->assertInstanceOf(ReminderMail::class, $mailable);
        $this->assertStringContainsString('Miete', $mailable->bodyText);
        $this->assertStringContainsString('15.08.2026', $mailable->bodyText);
    }

    public function test_standalone_reminder_uses_its_own_message(): void
    {
        $reminder = new Reminder();
        $reminder->forceFill([
            Reminder::name => 'Monatliche Notiz',
            Reminder::message => 'Bitte Kontoauszug prüfen.',
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        $schedule = new ReminderSchedule();
        $schedule->forceFill([
            ReminderSchedule::recurrence => 'MONTHLY_ON_DAY',
            ReminderSchedule::recurrence_value => 1,
        ]);

        $notification = new ReminderNotification($reminder, $schedule, CarbonImmutable::parse('2026-09-01'));

        $mailable = $notification->toMail(new class {
            public string $email = 'user@example.com';
            public string $name = 'Max Mustermann';
        });

        $this->assertSame('Bitte Kontoauszug prüfen.', $mailable->bodyText);
        $this->assertNull($mailable->targetUrl);
    }
}
