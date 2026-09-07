<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Actions\RunScheduledJobAction;
use App\Filament\App\Widgets\Dashboard\CategorySpendingWidget;
use App\Filament\App\Widgets\Dashboard\MonthlyBalanceChartWidget;
use App\Filament\App\Widgets\Dashboard\MonthlyBalanceStatsWidget;
use App\Filament\App\Widgets\Dashboard\PortfolioOverviewWidget;
use App\Filament\App\Widgets\Dashboard\UpcomingTransactionsTableWidget;
use App\Models\CustomDashboardUserWidget;
use App\Models\DashboardWidgetPreference;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;

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
        $registry = app(CustomDashboardWidgetTemplateRegistry::class);

        $standardWidgets = array_values(array_filter([
            $preferences->{DashboardWidgetPreference::show_monthly_balance_stats} ? MonthlyBalanceStatsWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_portfolio_overview} ? PortfolioOverviewWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_monthly_balance_chart} ? MonthlyBalanceChartWidget::class : null,
            $preferences->{DashboardWidgetPreference::show_upcoming_transactions_table} ? UpcomingTransactionsTableWidget::class : null,
            CategorySpendingWidget::class,
        ]));

        $customWidgets = CustomDashboardUserWidget::query()
            ->activeForUser($userId)
            ->get()
            ->reduce(function (array $widgets, CustomDashboardUserWidget $widget) use ($registry): array {
                $widgetClass = $registry->widgetClassFor($widget);

                if ($widgetClass !== null) {
                    $widgets[] = $widgetClass::make([
                        'customWidgetId' => (int)$widget->{CustomDashboardUserWidget::id},
                    ]);
                }

                return $widgets;
            }, []);

        return [...$standardWidgets, ...$customWidgets];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}
