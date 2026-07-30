<?php

return [
    'update_schedule' => [
        'time' => env('FIXED_COST_UPDATE_TIME', '00:15'),
    ],
    'reminder_schedule' => env('FIXED_COST_REMINDER_SCHEDULE', 'weekly'),
    'reminder_time' => env('FIXED_COST_REMINDER_TIME', '07:00'),

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
    ],
];
