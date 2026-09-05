<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\Financial\Insurance;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class InsurancesDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'expiring-insurances',
                label: 'Auslaufende Versicherungen',
                type: CustomDashboardWidgetTypeEnum::TABLE,
                configurationKeys: ['days', 'limit'],
                configurationSchema: fn(): array => [
                    TextInput::make('days')
                        ->label('Vorschau (Tage)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(730)
                        ->default(90)
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
                    'days' => 90,
                    'limit' => 10,
                ],
                dataResolver: fn(int $userId, array $configuration): array => [],
                tableConfigurator: function (Table $table, int $userId, array $configuration): Table {
                    $days = max(1, min(730, (int)$configuration['days']));
                    $limit = max(1, min(25, (int)$configuration['limit']));
                    $today = CarbonImmutable::today();

                    return $table
                        ->query(
                            Insurance::query()
                                ->where(Insurance::user_id, $userId)
                                ->whereBetween(Insurance::end_date, [
                                    $today->toDateString(),
                                    $today->addDays($days)->toDateString(),
                                ])
                                ->orderBy(Insurance::end_date)
                                ->limit($limit),
                        )
                        ->paginated(false)
                        ->columns([
                            TextColumn::make(Insurance::name)
                                ->label('Versicherung')
                                ->limit(30),
                            TextColumn::make(Insurance::company)
                                ->label('Gesellschaft')
                                ->placeholder('-'),
                            TextColumn::make(Insurance::end_date)
                                ->label('Enddatum')
                                ->date('d.m.Y'),
                        ]);
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'days' => max(1, min(730, (int)$configuration['days'])),
                    'limit' => max(1, min(25, (int)$configuration['limit'])),
                ],
            ),
        ];
    }
}
