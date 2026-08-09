<?php

return [
    'update_schedule' => [
        'time' => env('FIXED_COST_UPDATE_TIME', '00:15'),
    ],
    'reminders' => [
        'enabled' => env('FIXED_COST_REMINDERS_ENABLED', true),
        'schedule_time' => env('FIXED_COST_REMINDERS_TIME', '07:00'),
    ],

    'matching' => [
        /**
         * Mindest-Score (0–100) für eine automatische Verknüpfung.
         * Bei Gleichstand auf Top-Score wird immer ein manueller Vorschlag erstellt.
         */
        'threshold' => env('FIXED_COST_MATCHING_THRESHOLD', 70),

        /**
         * Uhrzeit für den täglichen Matching-Job.
         */
        'schedule_time' => env('FIXED_COST_MATCHING_TIME', '02:00'),

        /**
         * Job global aktivieren/deaktivieren.
         */
        'enabled' => env('FIXED_COST_MATCHING_ENABLED', true),

        /**
         * Lernende Regeln auf Basis von Auto-Linking und manuellen Entscheidungen.
         */
        'learning' => [
            'enabled' => env('FIXED_COST_MATCHING_LEARNING_ENABLED', true),
            'auto_learn_min_score' => env('FIXED_COST_MATCHING_LEARNING_AUTO_MIN_SCORE', 90),
            'auto_positive_weight' => env('FIXED_COST_MATCHING_LEARNING_AUTO_WEIGHT', 0.6),
            'accepted_positive_weight' => env('FIXED_COST_MATCHING_LEARNING_ACCEPT_WEIGHT', 1.0),
            'rejected_negative_weight' => env('FIXED_COST_MATCHING_LEARNING_REJECT_WEIGHT', 1.0),
            'reject_block_threshold' => env('FIXED_COST_MATCHING_LEARNING_REJECT_BLOCK_THRESHOLD', 2.0),
            'rule_confidence_min' => env('FIXED_COST_MATCHING_LEARNING_RULE_CONFIDENCE_MIN', 80),
            'amount_tolerance_percent' => env('FIXED_COST_MATCHING_LEARNING_AMOUNT_TOLERANCE_PERCENT', 5),
        ],
    ],

    'recurring' => [
        'enabled' => env('FIXED_COST_RECURRING_SUGGESTIONS_ENABLED', true),
        'schedule_time' => env('FIXED_COST_RECURRING_SUGGESTIONS_TIME', '03:00'),
        'min_occurrences' => env('FIXED_COST_RECURRING_SUGGESTIONS_MIN_OCCURRENCES', 3),
        'window_months' => env('FIXED_COST_RECURRING_SUGGESTIONS_WINDOW_MONTHS', 4),
        'amount_tolerance_percent' => env('FIXED_COST_RECURRING_SUGGESTIONS_AMOUNT_TOLERANCE_PERCENT', 3),
    ],
];
