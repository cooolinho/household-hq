<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Actions\RunScheduledJobAction;
use App\Filament\Admin\Widgets\Dashboard\CategorySpendingWidget;
use App\Filament\Admin\Widgets\Dashboard\MonthlyBalanceChartWidget;
use App\Filament\Admin\Widgets\Dashboard\MonthlyBalanceStatsWidget;
use App\Filament\Admin\Widgets\Dashboard\PortfolioOverviewWidget;
use App\Filament\Admin\Widgets\Dashboard\UpcomingTransactionsTableWidget;
use App\Models\DashboardWidgetPreference;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected function getHeaderActions(): array
    {
        return [
            RunScheduledJobAction::make(),
        ];
    }

    public function getWidgets(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            return [];
        }

        $preferences = DashboardWidgetPreference::forUser($userId);

        return array_values(array_filter([
            $preferences->{DashboardWidgetPreference::show_monthly_balance_stats} ? MonthlyBalanceStatsWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_portfolio_overview} ? PortfolioOverviewWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_monthly_balance_chart} ? MonthlyBalanceChartWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_upcoming_transactions_table} ? UpcomingTransactionsTableWidget::class : null,
            CategorySpendingWidget::class,
        ]));
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}

