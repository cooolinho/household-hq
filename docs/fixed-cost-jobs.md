# Fixed cost jobs

This document describes the fixed cost date updater and reminder jobs.

## What is included

- `FixedCostJob`: updates `next_booking_date` for all fixed costs.
- `financial_fixed_cost_reminders`: one or more reminder rules per fixed cost.
- `SendUpcomingFixedCostsReminderJob`: checks every day whether active reminders match the current `next_booking_date`.
- Reminders can be delivered by mail, by database notification, or by both.
- `FixedCostTransactionMatchingJob`: links unmatched transactions to fixed costs.
- `RecurringTransactionSuggestionDetectionJob`: detects recurring unmatched transactions and creates proposals.

## Rules

- If `next_booking_date` is reached or overdue, the updater advances it by `interval` until it is in the future.
- If `ends_mode=ENDS`, no new date is set after `ends_date`.
- Reminder rules are stored per `FixedCost` in their own table.
- Available reminder offsets: 1 day, 2 days, 3 days, 1 week, 2 weeks before `next_booking_date`.
- Reminders can be activated and deactivated individually.
- The job skips reminders that already fired for the current booking date.
- The job aborts immediately when reminders are globally disabled in config.
- Matching now learns from accepted/rejected suggestions and high-confidence auto-links.

## Configuration

Add these variables to `.env`:

- `FIXED_COST_UPDATE_TIME=00:15`
- `FIXED_COST_REMINDERS_ENABLED=true`
- `FIXED_COST_REMINDERS_TIME=07:00`
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

