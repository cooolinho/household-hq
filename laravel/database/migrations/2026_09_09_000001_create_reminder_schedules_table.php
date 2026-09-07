<?php

use App\Models\Enums\ReminderOffsetUnitEnum;
use App\Models\Enums\ReminderRecurrenceEnum;
use App\Models\Reminder;
use App\Models\ReminderSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(ReminderSchedule::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Reminder::class, ReminderSchedule::reminder_id)
                ->constrained(Reminder::TABLE)
                ->cascadeOnDelete();
            $table->unsignedSmallInteger(ReminderSchedule::offset_value)->nullable();
            $table->enum(ReminderSchedule::offset_unit, ReminderOffsetUnitEnum::allNames())->nullable();
            $table->enum(ReminderSchedule::recurrence, ReminderRecurrenceEnum::allNames())->nullable();
            $table->unsignedSmallInteger(ReminderSchedule::recurrence_value)->nullable();
            $table->date(ReminderSchedule::start_date)->nullable();
            $table->time(ReminderSchedule::run_at_time)->nullable();
            $table->dateTime(ReminderSchedule::next_due_at)->nullable();
            $table->dateTime(ReminderSchedule::last_sent_at)->nullable();
            $table->dateTime(ReminderSchedule::last_sent_for)->nullable();
            $table->boolean(ReminderSchedule::enabled)->default(true);

            $table->timestamps();

            $table->index([
                ReminderSchedule::reminder_id,
                ReminderSchedule::enabled,
            ], 'reminder_schedules_reminder_id_enabled_index');
            $table->index(ReminderSchedule::next_due_at, 'reminder_schedules_next_due_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ReminderSchedule::TABLE);
    }
};
