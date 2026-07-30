# Fixed cost jobs

This document describes the fixed cost date updater and reminder jobs.

## What is included

- `FixedCostJob`: updates `next_booking_date` for all fixed costs.
- `SendUpcomingFixedCostsReminderJob`: sends one mail per user with outgoing fixed costs in 3 windows:
  - next day
  - next week
  - next month
- `FixedCostTransactionMatchingJob`: links unmatched transactions to fixed costs.
- `RecurringTransactionSuggestionDetectionJob`: detects recurring unmatched transactions and creates proposals.

## Rules

- If `next_booking_date` is reached or overdue, the updater advances it by `interval` until it is in the future.
- If `ends_mode=ENDS`, no new date is set after `ends_date`.
- Reminder includes only outgoing fixed costs (`amount < 0`).
- Matching now learns from accepted/rejected suggestions and high-confidence auto-links.

## Configuration

Add these variables to `.env`:

- `FIXED_COST_UPDATE_TIME=00:15`
- `FIXED_COST_REMINDER_SCHEDULE=weekly` (`daily|weekly|monthly`)
- `FIXED_COST_REMINDER_TIME=07:00`
- `FIXED_COST_MATCHING_ENABLED=true`
- `FIXED_COST_MATCHING_TIME=02:00`
- `FIXED_COST_MATCHING_LEARNING_AUTO_MIN_SCORE=90`
- `FIXED_COST_MATCHING_LEARNING_REJECT_BLOCK_THRESHOLD=2`
- `FIXED_COST_RECURRING_SUGGESTIONS_ENABLED=true`
- `FIXED_COST_RECURRING_SUGGESTIONS_TIME=03:00`
- `FIXED_COST_RECURRING_SUGGESTIONS_MIN_OCCURRENCES=3`
- `FIXED_COST_RECURRING_SUGGESTIONS_WINDOW_MONTHS=4`

## Manual run

```bash
php artisan queue:work
php artisan schedule:run
```

