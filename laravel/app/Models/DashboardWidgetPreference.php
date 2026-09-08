<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidgetPreference extends Model
{
    const string TABLE = 'dashboard_widget_preferences';

    const string id = 'id';
    const string user_id = 'user_id';
    const string show_monthly_balance_stats = 'show_monthly_balance_stats';
    const string show_monthly_balance_chart = 'show_monthly_balance_chart';
    const string show_upcoming_transactions_table = 'show_upcoming_transactions_table';
    const string show_portfolio_overview = 'show_portfolio_overview';
    const string balance_mode = 'balance_mode';
    const string include_budgets_in_balance = 'include_budgets_in_balance';
    const string currency = 'currency';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';

    const string BALANCE_MODE_FORECAST = 'forecast';
    const string BALANCE_MODE_ACTUAL = 'actual';
    const string BALANCE_MODE_BOTH = 'both';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::show_monthly_balance_stats,
        self::show_monthly_balance_chart,
        self::show_upcoming_transactions_table,
        self::show_portfolio_overview,
        self::balance_mode,
        self::include_budgets_in_balance,
        self::currency,
    ];

    public static function forUser(int $userId): self
    {
        return static::query()->firstOrCreate(
            [self::user_id => $userId],
            static::defaults(),
        );
    }

    public static function defaults(): array
    {
        return [
            self::show_monthly_balance_stats => true,
            self::show_monthly_balance_chart => true,
            self::show_upcoming_transactions_table => true,
            self::show_portfolio_overview => true,
            self::balance_mode => self::BALANCE_MODE_BOTH,
            self::include_budgets_in_balance => true,
            self::currency => 'EUR',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    protected function casts(): array
    {
        return [
            self::show_monthly_balance_stats => 'boolean',
            self::show_monthly_balance_chart => 'boolean',
            self::show_upcoming_transactions_table => 'boolean',
            self::show_portfolio_overview => 'boolean',
            self::include_budgets_in_balance => 'boolean',
        ];
    }
}

