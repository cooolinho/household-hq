<?php

namespace App\Filament\App\Widgets\Dashboard\Concerns;

use App\Models\DashboardWidgetPreference;

trait UsesDashboardPreferences
{
    protected function getDashboardCurrency(): string
    {
        return strtoupper((string)($this->getDashboardPreferences()?->currency ?? 'EUR'));
    }

    protected function getDashboardPreferences(): ?DashboardWidgetPreference
    {
        $userId = $this->getDashboardUserId();

        if (!$userId) {
            return null;
        }

        return DashboardWidgetPreference::forUser($userId);
    }

    protected function getDashboardUserId(): ?int
    {
        return auth()->id();
    }

    protected function getDashboardBalanceMode(): string
    {
        return (string)($this->getDashboardPreferences()?->balance_mode ?? DashboardWidgetPreference::BALANCE_MODE_BOTH);
    }

    protected function shouldIncludeBudgetsInBalance(): bool
    {
        return (bool)($this->getDashboardPreferences()?->{DashboardWidgetPreference::include_budgets_in_balance} ?? true);
    }
}

