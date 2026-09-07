<?php

namespace App\Filament\App\Resources\Financial\Budgets\Schemas;

use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Financial\Budget;
use App\Models\Financial\TransactionCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allgemein')
                    ->columnSpanFull()
                    ->description('Name und Darstellung des Budgets.')
                    ->schema([
                        TextInput::make(Budget::name)
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Toggle::make(Budget::active)
                            ->label('Aktiv')
                            ->helperText('Inaktive Budgets werden weder ausgewertet noch benachrichtigt.')
                            ->default(true),
                        Textarea::make(Budget::description)
                            ->label('Beschreibung')
                            ->rows(2)
                            ->columnSpanFull(),
                        ToggleButtons::make(Budget::icon)
                            ->label('Icon')
                            ->options(BudgetIconEnum::options())
                            ->icons(BudgetIconEnum::iconMap())
                            ->default(BudgetIconEnum::default())
                            ->required()
                            ->inline()
                            ->columnSpanFull(),
                    ]),

                Section::make('Limit & Zeitraum')
                    ->columnSpanFull()
                    ->description('Wie viel darf im gewählten Zeitraum ausgegeben werden?')
                    ->columns(3)
                    ->schema([
                        TextInput::make(Budget::amount)
                            ->label('Limit')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('€')
                            ->required(),
                        TextInput::make(Budget::currency)
                            ->label('Währung')
                            ->maxLength(3)
                            ->default(Budget::DEFAULT_CURRENCY)
                            ->required(),
                        Select::make(Budget::period)
                            ->label('Zeitraum')
                            ->options(BudgetPeriodEnum::options())
                            ->default(BudgetPeriodEnum::default())
                            ->required(),
                    ]),

                Section::make('Transaktionskategorien')
                    ->columnSpanFull()
                    ->description('Der Verbrauch wird aus den Transaktionen dieser Kategorien ermittelt.')
                    ->schema([
                        Select::make(Budget::belongs_to_many_transaction_categories)
                            ->label('Kategorien')
                            ->relationship(titleAttribute: TransactionCategory::name)
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->options(fn(): array => TransactionCategory::query()
                                ->where(TransactionCategory::active, true)
                                ->visibleForUser((int)auth()->id())
                                ->with(TransactionCategory::belongs_to_parent)
                                ->get()
                                ->sortBy(fn(TransactionCategory $category): string => $category->getFullNameAttribute())
                                ->mapWithKeys(fn(TransactionCategory $category): array => [
                                    $category->getKey() => $category->getFullNameAttribute(),
                                ])
                                ->all())
                            ->columnSpanFull(),
                        Toggle::make(Budget::include_subcategories)
                            ->label('Unterkategorien einbeziehen')
                            ->helperText('Bei einer Hauptkategorie zählen auch die Transaktionen ihrer Unterkategorien.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Schwellwerte & Benachrichtigungen')
                    ->columnSpanFull()
                    ->description('Ab welchem Verbrauch wird gewarnt und wie soll benachrichtigt werden?')
                    ->columns(2)
                    ->schema([
                        TextInput::make(Budget::warning_threshold)
                            ->label('Warnung ab')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->suffix('%')
                            ->default(Budget::DEFAULT_WARNING_THRESHOLD)
                            ->required(),
                        TextInput::make(Budget::critical_threshold)
                            ->label('Überschreitung ab')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->suffix('%')
                            ->default(Budget::DEFAULT_CRITICAL_THRESHOLD)
                            ->required(),
                        Toggle::make(Budget::send_notification)
                            ->label('Benachrichtigung in der App')
                            ->default(true),
                        Toggle::make(Budget::send_mail)
                            ->label('E-Mail versenden')
                            ->default(false),
                    ]),
            ]);
    }
}
