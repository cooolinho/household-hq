<?php

namespace App\Filament\App\Resources\Financial\FixedCosts\Pages;

use App\Filament\App\Pages\FixedCostStatisticsPage;
use App\Filament\App\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostBalanceWidget;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostsExpensesTableWidget;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostsIncomeTableWidget;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostUpcomingBookingsWidget;
use App\Filament\App\Resources\Financial\FixedCosts\Widgets\FixedCostWeeklyOverviewWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;

class ListFixedCosts extends ListRecords
{
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('statistics')
                ->label('Statistiken')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray')
                ->url(FixedCostStatisticsPage::getUrl()),
            CreateAction::make(),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FixedCostBalanceWidget::class,
            FixedCostWeeklyOverviewWidget::class,
            FixedCostUpcomingBookingsWidget::class,
            FixedCostsIncomeTableWidget::class,
            FixedCostsExpensesTableWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

}
