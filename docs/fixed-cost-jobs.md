# Fixed cost jobs

This document describes the fixed cost date updater and reminder jobs.

## What is included

- `FixedCostJob`: updates `next_booking_date` for all fixed costs.
- `SendUpcomingFixedCostsReminderJob`: sends one mail per user with outgoing fixed costs in 3 windows:
  - next day
  - next week
  - next month

## Rules

- If `next_booking_date` is reached or overdue, the updater advances it by `interval` until it is in the future.
- If `ends_mode=ENDS`, no new date is set after `ends_date`.
- Reminder includes only outgoing fixed costs (`amount < 0`).

## Configuration

Add these variables to `.env`:

- `FIXED_COST_UPDATE_TIME=00:15`
- `FIXED_COST_REMINDER_SCHEDULE=weekly` (`daily|weekly|monthly`)
- `FIXED_COST_REMINDER_TIME=07:00`

## Manual run

```bash
php artisan queue:work
php artisan schedule:run
```

