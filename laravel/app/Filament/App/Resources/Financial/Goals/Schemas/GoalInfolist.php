<?php

namespace App\Filament\App\Resources\Financial\Goals\Schemas;

use App\Models\Financial\Goal;
use App\Services\Goal\GoalCalculationService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class GoalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('goal_details')
                    ->hiddenLabel(true)
                    ->html()
                    ->columnSpanFull()
                    ->state(function (Goal $record): HtmlString {
                        $service = app(GoalCalculationService::class);

                        return new HtmlString(
                            view('filament.app.resources.financial.goals.infolists.goal-details', [
                                'record' => $record,
                                'calculation' => $service->calculate($record),
                            ])->render()
                        );
                    }),
            ]);
    }
}
