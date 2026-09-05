<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\DashboardWidgetPreference;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\Financial\Transaction;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class BanksDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'bank-balance-trend',
                label: 'Bilanztrend',
                type: CustomDashboardWidgetTypeEnum::CHART,
                configurationKeys: ['currency', 'months'],
                configurationSchema: fn(): array => [
                    TextInput::make('currency')
                        ->label('Währung')
                        ->default('EUR')
                        ->maxLength(8)
                        ->required(),
                    TextInput::make('months')
                        ->label('Monate')
                        ->numeric()
                        ->minValue(3)
                        ->maxValue(24)
                        ->default(6)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => [
                    'currency' => 'EUR',
                    'months' => 6,
                ],
                dataResolver: function (int $userId, array $configuration): array {
                    $trend = app(DashboardMetricsService::class)->getMonthlyBalanceTrend(
                        $userId,
                        (string)$configuration['currency'],
                        (int)$configuration['months'],
                    );

                    return [
                        'labels' => $trend['labels'],
                        'datasets' => [
                            [
                                'label' => 'Prognose',
                                'data' => $trend['forecastBalances'],
                                'borderColor' => '#f59e0b',
                                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                                'tension' => 0.3,
                            ],
                            [
                                'label' => 'Ist',
                                'data' => $trend['actualBalances'],
                                'borderColor' => '#3b82f6',
                                'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                                'tension' => 0.3,
                            ],
                        ],
                    ];
                },
                chartType: 'line',
                configurationNormalizer: fn(array $configuration): array => [
                    'currency' => strtoupper(trim((string)$configuration['currency'])) ?: 'EUR',
                    'months' => max(3, min(24, (int)$configuration['months'])),
                ],
            ),
            new CustomDashboardWidgetTemplate(
                key: 'bank-balance-stats',
                label: 'Monatliche Bilanz',
                type: CustomDashboardWidgetTypeEnum::STAT,
                configurationKeys: ['currency', 'balance_mode'],
                configurationSchema: fn(): array => [
                    TextInput::make('currency')
                        ->label('Währung')
                        ->default('EUR')
                        ->maxLength(8)
                        ->required(),
                    Select::make('balance_mode')
                        ->label('Bilanzmodus')
                        ->options([
                            DashboardWidgetPreference::BALANCE_MODE_BOTH => 'Prognose + Ist',
                            DashboardWidgetPreference::BALANCE_MODE_FORECAST => 'Nur Prognose',
                            DashboardWidgetPreference::BALANCE_MODE_ACTUAL => 'Nur Ist',
                        ])
                        ->default(DashboardWidgetPreference::BALANCE_MODE_BOTH)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => [
                    'currency' => 'EUR',
                    'balance_mode' => DashboardWidgetPreference::BALANCE_MODE_BOTH,
                ],
                dataResolver: function (int $userId, array $configuration): array {
                    $metrics = app(DashboardMetricsService::class)->getMonthlyBalanceData(
                        $userId,
                        (string)$configuration['currency'],
                    );
                    $currency = $metrics['currency'];
                    $mode = (string)$configuration['balance_mode'];
                    $stats = [];

                    if (in_array($mode, [
                        DashboardWidgetPreference::BALANCE_MODE_BOTH,
                        DashboardWidgetPreference::BALANCE_MODE_FORECAST,
                    ], true)) {
                        $stats[] = [
                            'label' => 'Prognose Bilanz',
                            'value' => self::formatMoney($metrics['forecast']['balance'], $currency, true),
                            'description' => 'Aktive Fixkosten',
                            'color' => $metrics['forecast']['balance'] < 0 ? 'danger' : 'success',
                        ];
                    }

                    if (in_array($mode, [
                        DashboardWidgetPreference::BALANCE_MODE_BOTH,
                        DashboardWidgetPreference::BALANCE_MODE_ACTUAL,
                    ], true)) {
                        $stats[] = [
                            'label' => 'Ist Bilanz',
                            'value' => self::formatMoney($metrics['actual']['balance'], $currency, true),
                            'description' => 'Transaktionen im aktuellen Monat',
                            'color' => $metrics['actual']['balance'] < 0 ? 'danger' : 'success',
                        ];
                    }

                    return $stats;
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'currency' => strtoupper(trim((string)$configuration['currency'])) ?: 'EUR',
                    'balance_mode' => in_array((string)$configuration['balance_mode'], [
                        DashboardWidgetPreference::BALANCE_MODE_BOTH,
                        DashboardWidgetPreference::BALANCE_MODE_FORECAST,
                        DashboardWidgetPreference::BALANCE_MODE_ACTUAL,
                    ], true)
                        ? (string)$configuration['balance_mode']
                        : DashboardWidgetPreference::BALANCE_MODE_BOTH,
                ],
            ),
            new CustomDashboardWidgetTemplate(
                key: 'bank-recent-transactions',
                label: 'Letzte Transaktionen',
                type: CustomDashboardWidgetTypeEnum::TABLE,
                configurationKeys: ['currency', 'limit'],
                configurationSchema: fn(): array => [
                    TextInput::make('currency')
                        ->label('Währung')
                        ->default('EUR')
                        ->maxLength(8)
                        ->required(),
                    TextInput::make('limit')
                        ->label('Maximale Zeilen')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(25)
                        ->default(10)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => [
                    'currency' => 'EUR',
                    'limit' => 10,
                ],
                dataResolver: fn(int $userId, array $configuration): array => [],
                tableConfigurator: function (Table $table, int $userId, array $configuration): Table {
                    $currency = strtoupper((string)$configuration['currency']);
                    $limit = max(1, min(25, (int)$configuration['limit']));
                    $monthStart = CarbonImmutable::today()->startOfMonth();

                    return $table
                        ->query(
                            Transaction::query()
                                ->where(Transaction::user_id, $userId)
                                ->where(Transaction::date, '>=', $monthStart->toDateString())
                                ->whereRaw('UPPER(' . Transaction::amount_currency . ') = ?', [$currency])
                                ->latest(Transaction::date)
                                ->limit($limit),
                        )
                        ->paginated(false)
                        ->columns([
                            TextColumn::make(Transaction::date)
                                ->label('Datum')
                                ->date('d.m.Y'),
                            TextColumn::make(Transaction::payer)
                                ->label('Auftraggeber')
                                ->limit(20)
                                ->placeholder('-'),
                            TextColumn::make(Transaction::purpose)
                                ->label('Verwendungszweck')
                                ->limit(35)
                                ->placeholder('-'),
                            TextColumn::make(Transaction::amount)
                                ->label('Betrag')
                                ->alignEnd()
                                ->formatStateUsing(
                                    fn(mixed $state): string => number_format((float)$state, 2, ',', '.') . ' ' . $currency,
                                ),
                        ]);
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'currency' => strtoupper(trim((string)$configuration['currency'])) ?: 'EUR',
                    'limit' => max(1, min(25, (int)$configuration['limit'])),
                ],
            ),
        ];
    }

    private static function formatMoney(float $value, string $currency, bool $signed = false): string
    {
        $prefix = $signed && $value > 0 ? '+' : '';

        return $prefix . number_format($value, 2, ',', '.') . ' ' . $currency;
    }
}
