<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\Financial\FixedCost;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FixedCostsDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'fixed-cost-monthly-balance',
                label: 'Monatliche Fixkostenbilanz',
                type: CustomDashboardWidgetTypeEnum::STAT,
                configurationKeys: ['currency'],
                configurationSchema: fn(): array => [
                    TextInput::make('currency')
                        ->label('Währung')
                        ->default('EUR')
                        ->maxLength(8)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => ['currency' => 'EUR'],
                dataResolver: function (int $userId, array $configuration): array {
                    $metrics = app(DashboardMetricsService::class)->getMonthlyBalanceData(
                        $userId,
                        (string)$configuration['currency'],
                    );
                    $balance = $metrics['forecast']['balance'];
                    $currency = $metrics['currency'];

                    return [[
                        'label' => 'Monatliche Bilanz',
                        'value' => self::formatMoney($balance, $currency, true),
                        'description' => 'Gewichtete aktive Fixkosten',
                        'color' => $balance < 0 ? 'danger' : 'success',
                    ]];
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'currency' => strtoupper(trim((string)$configuration['currency'])) ?: 'EUR',
                ],
            ),
            new CustomDashboardWidgetTemplate(
                key: 'upcoming-fixed-costs',
                label: 'Bevorstehende Fixkosten',
                type: CustomDashboardWidgetTypeEnum::TABLE,
                configurationKeys: ['days', 'limit'],
                configurationSchema: fn(): array => [
                    TextInput::make('days')
                        ->label('Vorschau (Tage)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(30)
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
                    'days' => 30,
                    'limit' => 10,
                ],
                dataResolver: fn(int $userId, array $configuration): array => [],
                tableConfigurator: function (Table $table, int $userId, array $configuration): Table {
                    $days = max(1, min(365, (int)$configuration['days']));
                    $limit = max(1, min(25, (int)$configuration['limit']));
                    $today = CarbonImmutable::today();

                    return $table
                        ->query(
                            FixedCost::query()
                                ->where(FixedCost::user_id, $userId)
                                ->whereNotNull(FixedCost::next_booking_date)
                                ->whereBetween(FixedCost::next_booking_date, [
                                    $today->toDateString(),
                                    $today->addDays($days)->toDateString(),
                                ])
                                ->orderBy(FixedCost::next_booking_date)
                                ->limit($limit),
                        )
                        ->paginated(false)
                        ->columns([
                            TextColumn::make(FixedCost::name)
                                ->label('Name')
                                ->limit(30),
                            TextColumn::make(FixedCost::next_booking_date)
                                ->label('Nächste Buchung')
                                ->date('d.m.Y'),
                            TextColumn::make(FixedCost::amount)
                                ->label('Betrag')
                                ->alignEnd()
                                ->formatStateUsing(
                                    fn(mixed $state): string => number_format((float)$state, 2, ',', '.'),
                                ),
                        ]);
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'days' => max(1, min(365, (int)$configuration['days'])),
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
