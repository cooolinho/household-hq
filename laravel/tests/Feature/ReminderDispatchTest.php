<?php

namespace Tests\Feature;

use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\Reminder\ReminderDispatcher;
use App\Settings\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_anchored_reminder_exactly_when_due(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 08:05'));

        $this->assertSame(1, $sent);
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    private function makeFixedCost(User $user, string $nextBookingDate, array $overrides = []): FixedCost
    {
        return FixedCost::create(array_merge([
            FixedCost::user_id => $user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => 1000,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::ends_mode => FixedCostEndsModeEnum::NONE->name,
            FixedCost::next_booking_date => $nextBookingDate,
        ], $overrides));
    }

    private function dispatcher(): ReminderDispatcher
    {
        return app(ReminderDispatcher::class);
    }

    public function test_it_does_not_send_the_same_reminder_twice_for_the_same_anchor(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 08:05'));
        $secondRunSent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 09:05'));

        $this->assertSame(0, $secondRunSent);
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    public function test_it_catches_up_a_missed_run_within_the_catch_up_window(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        // due at 2026-08-15 08:00, catch-up default is 48h -> job runs late at +30h
        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-16 14:00'));

        $this->assertSame(1, $sent);
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    public function test_it_does_not_catch_up_after_the_catch_up_window_has_passed(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        // due at 2026-08-15 08:00, catch-up default is 48h -> +100h is far outside the window
        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-19 12:00'));

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
    }

    public function test_disabled_reminder_is_skipped(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => false,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 08:05'));

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
    }

    public function test_it_sends_a_reminder_anchored_on_the_extended_date(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-06-01', [
            FixedCost::ends_mode => FixedCostEndsModeEnum::EXTENDED->name,
            FixedCost::extended_date => '2026-12-01',
            FixedCost::extended_interval => FixedCostIntervalEnum::YEARLY->name,
        ]);

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Vertragsverlängerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::extended_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'MONTH',
            ReminderSchedule::offset_value => 1,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-11-01 08:05'));

        $this->assertSame(1, $sent);
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    public function test_a_standalone_reminder_advances_its_next_due_date_after_firing(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Monatliche Notiz',
            Reminder::message => 'Bitte Kontoauszug prüfen.',
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        $schedule = ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::recurrence => 'MONTHLY_ON_DAY',
            ReminderSchedule::recurrence_value => 1,
            ReminderSchedule::start_date => '2026-08-01',
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $firstRunSent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-01 08:05'));
        $this->assertSame(1, $firstRunSent);

        $schedule->refresh();
        $this->assertSame('2026-09-01 08:00:00', $schedule->{ReminderSchedule::next_due_at}->toDateTimeString());

        // Not due yet, still on the same computed next occurrence.
        $secondRunSent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 08:05'));
        $this->assertSame(0, $secondRunSent);

        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    public function test_reminder_without_email_falls_back_to_database_channel(): void
    {
        Notification::fake();
        $user = User::factory()->create([User::email => '']);
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $sent = $this->dispatcher()->dispatchDue(CarbonImmutable::parse('2026-08-15 08:05'));

        $this->assertSame(1, $sent);
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    public function test_global_kill_switch_stops_the_job_from_dispatching(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, '2026-08-22');

        $reminder = Reminder::create([
            Reminder::user_id => $user->id,
            Reminder::name => 'Miete Erinnerung',
            Reminder::remindable_type => FixedCost::class,
            Reminder::remindable_id => $fixedCost->id,
            Reminder::date_property => FixedCost::next_booking_date,
            Reminder::send_mail => true,
            Reminder::send_notification => true,
            Reminder::enabled => true,
        ]);

        ReminderSchedule::create([
            ReminderSchedule::reminder_id => $reminder->id,
            ReminderSchedule::offset_unit => 'DAY',
            ReminderSchedule::offset_value => 7,
            ReminderSchedule::run_at_time => '08:00',
            ReminderSchedule::enabled => true,
        ]);

        $settings = app(ReminderSettings::class);
        $settings->enabled = false;
        $settings->save();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 08:05'));
        app(\App\Jobs\Scheduled\SendRemindersJob::class)->handle(
            $this->dispatcher(),
            app(ReminderSettings::class),
            app(\App\Services\FixedCostNextBookingDateUpdater::class),
            app(\App\Settings\FixedCostSettings::class),
        );
        CarbonImmutable::setTestNow();

        Notification::assertNothingSent();
    }
}
