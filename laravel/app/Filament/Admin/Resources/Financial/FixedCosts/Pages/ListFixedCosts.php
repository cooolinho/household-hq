<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Resources\Financial\FixedCosts\Widgets\FixedCostsDashboardWidget;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListFixedCosts extends ListRecords
{
    const bool SHOW_MAIN_TABLE = false;
    protected static string $resource = FixedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                FixedCostResource::getSendFixedCostsReminderNowAction(),
            ])
            ->button(),
        ];
    }

//    public function getHeaderWidgets(): array
//    {
//        return [
//            FixedCostsIncomeTableWidget::class,
//            FixedCostsExpensesTableWidget::class,
//        ];
//    }

    protected function getFooterWidgets(): array
    {
        return [
            FixedCostsDashboardWidget::class,
        ];
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

//        return $schema
//            ->components([
//                $this->getTabsContentComponent(),
//                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
//                self::SHOW_MAIN_TABLE ? EmbeddedTable::make() : null,
//                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
//            ]);
    }

}

