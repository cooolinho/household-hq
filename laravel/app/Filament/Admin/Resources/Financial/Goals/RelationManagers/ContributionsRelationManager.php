<?php

namespace App\Filament\Admin\Resources\Financial\Goals\RelationManagers;

use App\Filament\Admin\Resources\Financial\Goals\Schemas\GoalContributionForm;
use App\Models\Financial\Goal;
use App\Models\Financial\GoalContribution;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Manuelle Einzahlungen/Tilgungen – echte hasMany-Relation mit vollem CRUD,
 * anders als der reine Lesezugriff auf Transaktionen.
 */
class ContributionsRelationManager extends RelationManager
{
    protected static string $relationship = Goal::has_many_contributions;
    protected static ?string $title = 'Manuelle Einzahlungen';

    public function form(Schema $schema): Schema
    {
        return GoalContributionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(GoalContribution::note)
            ->defaultSort(GoalContribution::date, 'desc')
            ->columns([
                TextColumn::make(GoalContribution::date)
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make(GoalContribution::amount)
                    ->label('Betrag')
                    ->formatStateUsing(fn(float $state): string => number_format($state, 2, ',', '.') . ' €')
                    ->sortable(),
                TextColumn::make(GoalContribution::note)
                    ->label('Notiz')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
