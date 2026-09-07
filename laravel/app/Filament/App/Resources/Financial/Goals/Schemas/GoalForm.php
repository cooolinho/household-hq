<?php

namespace App\Filament\App\Resources\Financial\Goals\Schemas;

use App\Models\Enums\GoalDirectionEnum;
use App\Models\Enums\GoalIconEnum;
use App\Models\Enums\GoalTypeEnum;
use App\Models\Financial\Goal;
use App\Models\Financial\TransactionCategory;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allgemein')
                    ->columnSpanFull()
                    ->description('Name und Darstellung des Ziels.')
                    ->schema([
                        TextInput::make(Goal::name)
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Toggle::make(Goal::active)
                            ->label('Aktiv')
                            ->helperText('Inaktive Ziele werden weder in der Übersicht noch im Badge gezählt.')
                            ->default(true),
                        Textarea::make(Goal::description)
                            ->label('Beschreibung')
                            ->rows(2)
                            ->columnSpanFull(),
                        ToggleButtons::make(Goal::icon)
                            ->label('Icon')
                            ->options(GoalIconEnum::options())
                            ->icons(GoalIconEnum::iconMap())
                            ->default(GoalIconEnum::default())
                            ->required()
                            ->inline()
                            ->columnSpanFull(),
                        FileUpload::make(Goal::image_path)
                            ->label('Motivationsfoto')
                            ->disk(Goal::STORAGE_DISK)
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Zieltyp & Beträge')
                    ->columnSpanFull()
                    ->description('Abzahlung oder Sparziel – Startwert und Zielbetrag legen die Richtung fest.')
                    ->columns(3)
                    ->schema([
                        ToggleButtons::make(Goal::type)
                            ->label('Art des Ziels')
                            ->options(GoalTypeEnum::options())
                            ->default(GoalTypeEnum::default())
                            ->required()
                            ->inline()
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make(Goal::start_amount)
                            ->label(fn(Get $get): string => GoalTypeEnum::tryFrom((string)$get(Goal::type))?->startLabel()
                                ?? 'Startwert')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('€')
                            ->default(0)
                            ->required(),
                        TextInput::make(Goal::target_amount)
                            ->label(fn(Get $get): string => GoalTypeEnum::tryFrom((string)$get(Goal::type))?->targetLabel()
                                ?? 'Zielbetrag')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('€')
                            ->required()
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $type = GoalTypeEnum::tryFrom((string)$get(Goal::type));
                                    $startAmount = (float)$get(Goal::start_amount);
                                    $targetAmount = (float)$value;

                                    if ($type === GoalTypeEnum::SAVINGS && $targetAmount <= $startAmount) {
                                        $fail('Der Zielbetrag muss über dem Startbetrag liegen.');
                                    }

                                    if ($type === GoalTypeEnum::DEBT_PAYOFF && $targetAmount >= $startAmount) {
                                        $fail('Der Zielbetrag muss unter der Startschuld liegen.');
                                    }
                                },
                            ]),
                        TextInput::make(Goal::currency)
                            ->label('Währung')
                            ->maxLength(3)
                            ->default(Goal::DEFAULT_CURRENCY)
                            ->required(),
                    ]),

                Section::make('Zeitraum')
                    ->columnSpanFull()
                    ->description('Ab wann zählen Beiträge und bis wann soll das Ziel erreicht sein?')
                    ->columns(2)
                    ->schema([
                        DatePicker::make(Goal::start_date)
                            ->label('Startdatum')
                            ->helperText('Nur Transaktionen und Einzahlungen ab diesem Datum zählen zum Fortschritt.')
                            ->default(now())
                            ->required(),
                        DatePicker::make(Goal::target_date)
                            ->label('Zieldatum (optional)')
                            ->helperText('Wird für die Prognose der nötigen Monatsrate genutzt.')
                            ->after(Goal::start_date),
                    ]),

                Section::make('Transaktionskategorien')
                    ->columnSpanFull()
                    ->description('Der Fortschritt wird zusätzlich zu manuellen Einzahlungen aus den Transaktionen dieser Kategorien ermittelt.')
                    ->schema([
                        Select::make(Goal::belongs_to_many_transaction_categories)
                            ->label('Kategorien')
                            ->relationship(titleAttribute: TransactionCategory::name)
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Optional – ein Ziel kann auch rein über manuelle Einzahlungen geführt werden.')
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
                        Toggle::make(Goal::include_subcategories)
                            ->label('Unterkategorien einbeziehen')
                            ->helperText('Bei einer Hauptkategorie zählen auch die Transaktionen ihrer Unterkategorien.')
                            ->default(true)
                            ->columnSpanFull(),
                        ToggleButtons::make(Goal::direction)
                            ->label('Zählrichtung')
                            ->options(GoalDirectionEnum::options())
                            ->default(GoalDirectionEnum::default())
                            ->required()
                            ->inline()
                            ->helperText(fn(Get $get): string => GoalDirectionEnum::tryFrom((string)$get(Goal::direction))?->helperText()
                                ?? '')
                            ->live()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
