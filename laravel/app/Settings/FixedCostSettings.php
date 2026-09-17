<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FixedCostSettings extends Settings
{
    public bool $update_due_dates_enabled = true;
    public string $update_schedule_time;

    public int $matching_threshold;
    public string $matching_schedule_time;
    public bool $matching_enabled;

    public bool $matching_learning_enabled;
    public int $matching_learning_auto_learn_min_score;
    public float $matching_learning_auto_positive_weight;
    public float $matching_learning_accepted_positive_weight;
    public float $matching_learning_rejected_negative_weight;
    public float $matching_learning_reject_block_threshold;
    public int $matching_learning_rule_confidence_min;
    public int $matching_learning_amount_tolerance_percent;

    public int $matching_category_weight;
    public bool $matching_category_mismatch_blocks_auto_link;

    public bool $recurring_enabled;
    public string $recurring_schedule_time;
    public int $recurring_min_occurrences;
    public int $recurring_window_months;
    public int $recurring_amount_tolerance_percent;

    public bool $booking_date_suggestions_enabled;
    public string $booking_date_schedule_time;
    public int $booking_date_min_deviation_days;
    public int $booking_date_window_months;
    public int $booking_date_min_occurrences;

    public static function group(): string
    {
        return 'fixed_costs';
    }
}

