<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('fixed_costs.booking_date_suggestions_enabled', true);
        $this->migrator->add('fixed_costs.booking_date_schedule_time', '05:00');
        $this->migrator->add('fixed_costs.booking_date_min_deviation_days', 2);
        $this->migrator->add('fixed_costs.booking_date_window_months', 12);
        $this->migrator->add('fixed_costs.booking_date_min_occurrences', 3);
    }
};
