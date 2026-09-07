<?php

namespace App\Filament\App\Resources\Financial\Budgets\Schemas;

use App\Models\Financial\Budget;
use App\Services\Budget\BudgetCalculationService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BudgetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('budget_details')
                    ->hiddenLabel(true)
                    ->html()
                    ->columnSpanFull()
                    ->state(function (Budget $record): HtmlString {
                        $service = app(BudgetCalculationService::class);

                        return new HtmlString(
                            view('filament.app.resources.financial.budgets.infolists.budget-details', [
                                'record' => $record,
                                'calculation' => $service->calculate($record),
                                'history' => $service->history($record),
                            ])->render()
                        );
                    }),
            ]);
    }
}
