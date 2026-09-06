<?php

namespace App\Filament\Admin\Resources\Financial\Goals\Pages;

use App\Filament\Admin\Resources\Financial\Goals\GoalResource;
use App\Models\Financial\Goal;
use App\Services\Goal\GoalCalculation;
use App\Services\Goal\GoalCalculationService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;

/**
 * Übersicht aller Ziele als Karten-Grid mit Fortschrittsbalken statt einer Tabelle.
 */
class ListGoals extends Page
{
    protected static string $resource = GoalResource::class;

    protected string $view = 'filament.admin.resources.financial.goals.pages.list-goals';

    public function getTitle(): string
    {
        return 'Ziele';
    }

    /**
     * @return list<GoalCalculation>
     */
    public function goalCalculations(): array
    {
        return app(GoalCalculationService::class)->calculateForUser((int)auth()->id());
    }

    public function hasInactiveGoals(): bool
    {
        return Goal::query()
            ->where(Goal::user_id, auth()->id())
            ->where(Goal::active, false)
            ->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ziel anlegen'),
        ];
    }
}
