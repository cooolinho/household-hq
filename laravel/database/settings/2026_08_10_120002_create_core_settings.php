<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('fixed_costs.update_schedule_time', '00:15');
        $this->migrator->add('fixed_costs.reminders_enabled', true);
        $this->migrator->add('fixed_costs.reminders_schedule_time', '07:00');

        $this->migrator->add('fixed_costs.matching_threshold', 70);
        $this->migrator->add('fixed_costs.matching_schedule_time', '02:00');
        $this->migrator->add('fixed_costs.matching_enabled', true);

        $this->migrator->add('fixed_costs.matching_learning_enabled', true);
        $this->migrator->add('fixed_costs.matching_learning_auto_learn_min_score', 90);
        $this->migrator->add('fixed_costs.matching_learning_auto_positive_weight', 0.6);
        $this->migrator->add('fixed_costs.matching_learning_accepted_positive_weight', 1.0);
        $this->migrator->add('fixed_costs.matching_learning_rejected_negative_weight', 1.0);
        $this->migrator->add('fixed_costs.matching_learning_reject_block_threshold', 2.0);
        $this->migrator->add('fixed_costs.matching_learning_rule_confidence_min', 80);
        $this->migrator->add('fixed_costs.matching_learning_amount_tolerance_percent', 5);

        $this->migrator->add('fixed_costs.recurring_enabled', true);
        $this->migrator->add('fixed_costs.recurring_schedule_time', '03:00');
        $this->migrator->add('fixed_costs.recurring_min_occurrences', 3);
        $this->migrator->add('fixed_costs.recurring_window_months', 4);
        $this->migrator->add('fixed_costs.recurring_amount_tolerance_percent', 3);

        $this->migrator->add('imap_import.enabled', true);
        $this->migrator->add('imap_import.schedule_minutes', 15);
    }
};

