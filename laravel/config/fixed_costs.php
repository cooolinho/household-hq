<?php

return [
    'update_schedule' => [
        'time' => env('FIXED_COST_UPDATE_TIME', '00:15'),
    ],
    'reminder_schedule' => env('FIXED_COST_REMINDER_SCHEDULE', 'weekly'),
    'reminder_time' => env('FIXED_COST_REMINDER_TIME', '07:00'),
];

