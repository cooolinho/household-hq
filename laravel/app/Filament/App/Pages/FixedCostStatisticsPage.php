<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsBookingsTableWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsCategoryChartWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsOverviewWidget;
use App\Filament\App\Widgets\FixedCostStatistics\FixedCostStatisticsTrendChartWidget;
use App\Menu\NavigationGroup;
use App\Models\DashboardWidgetPreference;
use App\Models\Enums\FixedCostStatisticsPeriodEnum;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Fixkosten-Statistiken über einen dynamisch wählbaren Zeitraum (Preset oder benutzerdefiniert).
 */
class FixedCostStatisticsPage extends Page
{
    use HasFiltersForm;

    public const string FILTER_PRESET = 'preset';

    public const string FILTER_FROM = 'from';

    public const string FILTER_TO = 'to';

    public const string FILTER_INCLUDE_BUDGETS = 'includeBudgets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::FIXED_COSTS;

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return 'Statistiken';
    }

    public function getTitle(): string
    {
        return 'Fixkosten-Statistiken';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('filtersForm'),
                Grid::make($this->getColumns())
                    ->schema(fn(): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zeitraum & Optionen')
                    ->columnSpanFull()
                    ->schema([
                        Select::make(self::FILTER_PRESET)
                            ->label('Zeitraum')
                            ->options(FixedCostStatisticsPeriodEnum::options())
                            ->default(FixedCostStatisticsPeriodEnum::default())
                            ->selectablePlaceholder(false)
                            ->live(),

                        DatePicker::make(self::FILTER_FROM)
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->default(fn(): string => CarbonImmutable::today()->startOfMonth()->subMonths(5)->toDateString())
                            ->maxDate(fn(Get $get) => $get(self::FILTER_TO))
                            ->visible(fn(Get $get): bool => $get(self::FILTER_PRESET) === FixedCostStatisticsPeriodEnum::CUSTOM->name),

                        DatePicker::make(self::FILTER_TO)
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->default(fn(): string => CarbonImmutable::today()->endOfMonth()->toDateString())
                            ->minDate(fn(Get $get) => $get(self::FILTER_FROM))
                            ->visible(fn(Get $get): bool => $get(self::FILTER_PRESET) === FixedCostStatisticsPeriodEnum::CUSTOM->name),

                        Toggle::make(self::FILTER_INCLUDE_BUDGETS)
                            ->label('Budgets einrechnen')
                            ->helperText('Alle aktiven, markierten Budgets als eine Ausgabe.')
                            ->default(fn(): bool => (bool)DashboardWidgetPreference::forUser((int)auth()->id())
                                ->{DashboardWidgetPreference::include_budgets_in_balance})
                            ->live()
                            ->afterStateUpdated(fn(bool $state): mixed => DashboardWidgetPreference::forUser((int)auth()->id())
                                ->update([DashboardWidgetPreference::include_budgets_in_balance => $state])),
                    ]),
            ]);
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            FixedCostStatisticsOverviewWidget::class,
            FixedCostStatisticsTrendChartWidget::class,
            FixedCostStatisticsCategoryChartWidget::class,
            FixedCostStatisticsBookingsTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}
