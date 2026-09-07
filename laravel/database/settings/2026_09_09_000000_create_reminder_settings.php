<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('reminders.enabled', true);
        $this->migrator->add('reminders.default_run_at_time', '08:00');
        $this->migrator->add('reminders.catch_up_hours', 48);

        $this->migrator->deleteIfExists('fixed_costs.reminders_enabled');
        $this->migrator->deleteIfExists('fixed_costs.reminders_schedule_time');
    }
};
