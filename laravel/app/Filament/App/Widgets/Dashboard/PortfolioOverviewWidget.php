<?php

namespace App\Filament\App\Widgets\Dashboard;

use App\Filament\App\Widgets\Dashboard\Concerns\UsesDashboardPreferences;
use App\Services\DashboardMetricsService;
use Filament\Widgets\Widget;

class PortfolioOverviewWidget extends Widget
{
    use UsesDashboardPreferences;

    public int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected string $view = 'filament.app.widgets.dashboard.portfolio-overview-widget';

    protected function getViewData(): array
    {
        $userId = $this->getDashboardUserId();

        if (!$userId) {
            return [
                'currency' => 'EUR',
                'items' => [],
                'alerts' => [],
            ];
        }

        $data = app(DashboardMetricsService::class)
            ->getOverviewData($userId, $this->getDashboardCurrency());

        return [
            'currency' => $data['currency'],
            'items' => [
                ['label' => 'Fixkosten', 'value' => (int)$data['fixedCostsCount']],
                ['label' => 'Versicherungen', 'value' => (int)$data['insurancesCount']],
                ['label' => 'Bankkonten', 'value' => (int)$data['bankAccountsCount']],
                ['label' => 'Dokumente', 'value' => (int)$data['documentsCount']],
                ['label' => 'Importierte E-Mails', 'value' => (int)$data['importedEmailsCount']],
                ['label' => 'Messgeraete', 'value' => (int)$data['measurementDevicesCount']],
            ],
            'alerts' => [
                'unmatchedTransactionsThisMonthCount' => (int)$data['unmatchedTransactionsThisMonthCount'],
                'upcomingExpensesCount' => (int)$data['upcomingExpensesCount'],
                'upcomingExpensesTotal' => (float)$data['upcomingExpensesTotal'],
                'expiringInsurancesCount' => (int)$data['expiringInsurancesCount'],
                'nonPreferredCurrencyTransactionsCount' => (int)$data['nonPreferredCurrencyTransactionsCount'],
                'transactionsThisMonthCount' => (int)$data['transactionsThisMonthCount'],
            ],
        ];
    }
}

