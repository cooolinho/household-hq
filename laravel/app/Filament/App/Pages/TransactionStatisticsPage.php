<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\Statistics\CategoryBarChartWidget;
use App\Filament\App\Widgets\Statistics\CategoryPieChartWidget;
use App\Filament\App\Widgets\Statistics\CategoryStatsOverviewWidget;
use App\Filament\App\Widgets\Statistics\MonthlyCategoryTrendWidget;
use App\Jobs\Scheduled\RefreshTransactionStatisticsJob;
use App\Menu\NavigationGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TransactionStatisticsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::BANKS;
    protected static ?int $navigationSort = 45;
    protected string $view = 'filament.app.pages.transaction-statistics-page';

    public static function getNavigationLabel(): string
    {
        return 'Statistiken';
    }

    public function getTitle(): string
    {
        return 'Statistiken';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            CategoryStatsOverviewWidget::class,
            CategoryPieChartWidget::class,
            CategoryBarChartWidget::class,
            MonthlyCategoryTrendWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Statistiken aktualisieren')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function () {
                    RefreshTransactionStatisticsJob::dispatch(auth()->id());

                    Notification::make()
                        ->title('Statistiken werden aktualisiert')
                        ->body('Der Cache wird im Hintergrund neu aufgebaut.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
