<?php

namespace App\Filament\App\Resources\Financial\Goals\Schemas;

use App\Models\Financial\GoalContribution;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoalContributionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make(GoalContribution::date)
                    ->label('Datum')
                    ->default(now())
                    ->required(),
                TextInput::make(GoalContribution::amount)
                    ->label('Betrag')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->required(),
                TextInput::make(GoalContribution::note)
                    ->label('Notiz')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
