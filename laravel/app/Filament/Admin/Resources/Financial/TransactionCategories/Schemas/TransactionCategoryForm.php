<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Schemas;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TransactionCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make(TransactionCategory::name)
                ->label('Name')
                ->required()
                ->maxLength(255),

            Select::make(TransactionCategory::parent_id)
                ->label('Hauptkategorie')
                ->nullable()
                ->placeholder('Keine (Hauptkategorie)')
                ->searchable()
                ->options(function (?TransactionCategory $record): array {
                    $query = TransactionCategory::query()
                        ->where(TransactionCategory::user_id, auth()->id())
                        ->whereNull(TransactionCategory::parent_id)
                        ->orderBy(TransactionCategory::name);

                    // Avoid circular reference: exclude self
                    if ($record?->id) {
                        $query->where(TransactionCategory::id, '!=', $record->id);
                    }

                    return $query->pluck(TransactionCategory::name, TransactionCategory::id)->toArray();
                }),

            Toggle::make(TransactionCategory::active)
                ->label('Aktiv')
                ->default(true),

            Repeater::make(TransactionCategory::has_many_rules)
                ->label('Regeln')
                ->relationship()
                ->addActionLabel('Regel hinzufügen')
                ->collapsible()
                ->columnSpanFull()
                ->itemLabel(fn(array $state): string => 'Regel (' . ($state[TransactionCategoryRule::operator] ?? 'AND') . ')')
                ->schema([
                    Select::make(TransactionCategoryRule::operator)
                        ->label('Verknüpfung der Kriterien')
                        ->options([
                            TransactionCategoryRule::OPERATOR_AND => 'AND — Alle Kriterien müssen zutreffen',
                            TransactionCategoryRule::OPERATOR_OR => 'OR — Mindestens ein Kriterium muss zutreffen',
                        ])
                        ->default(TransactionCategoryRule::OPERATOR_AND)
                        ->required()
                        ->live(),

                    Toggle::make(TransactionCategoryRule::active)
                        ->label('Aktiv')
                        ->default(true),

                    Repeater::make(TransactionCategoryRule::has_many_criteria)
                        ->label('Kriterien')
                        ->relationship()
                        ->addActionLabel('Kriterium hinzufügen')
                        ->schema([
                            Select::make(TransactionCategoryCriterion::field)
                                ->label('Feld')
                                ->options([
                                    TransactionCategoryCriterion::FIELD_PAYER => 'Auftraggeber (payer)',
                                    TransactionCategoryCriterion::FIELD_PURPOSE => 'Verwendungszweck (purpose)',
                                    TransactionCategoryCriterion::FIELD_DESCRIPTION => 'Beschreibung (description)',
                                    TransactionCategoryCriterion::FIELD_AMOUNT => 'Betrag (amount)',
                                    TransactionCategoryCriterion::FIELD_AMOUNT_CURRENCY => 'Währung (amount_currency)',
                                    TransactionCategoryCriterion::FIELD_DATE => 'Datum (date)',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Select $component) => $component
                                    ->getContainer()
                                    ->getComponent(TransactionCategoryCriterion::operator)
                                    ?->state(null)
                                ),

                            Select::make(TransactionCategoryCriterion::operator)
                                ->label('Operator')
                                ->options(function (Get $get): array {
                                    $field = $get(TransactionCategoryCriterion::field);

                                    $numericFields = TransactionCategoryCriterion::getNumericFields();

                                    if (in_array($field, $numericFields, true)) {
                                        return [
                                            TransactionCategoryCriterion::OP_EQUALS => 'Ist gleich',
                                            TransactionCategoryCriterion::OP_GREATER_THAN => 'Größer als',
                                            TransactionCategoryCriterion::OP_LESS_THAN => 'Kleiner als',
                                            TransactionCategoryCriterion::OP_BETWEEN => 'Zwischen',
                                        ];
                                    }

                                    return [
                                        TransactionCategoryCriterion::OP_CONTAINS => 'Enthält',
                                        TransactionCategoryCriterion::OP_EQUALS => 'Ist gleich',
                                        TransactionCategoryCriterion::OP_STARTS_WITH => 'Beginnt mit',
                                        TransactionCategoryCriterion::OP_ENDS_WITH => 'Endet mit',
                                        TransactionCategoryCriterion::OP_REGEX => 'Regex',
                                    ];
                                })
                                ->required()
                                ->live(),

                            TextInput::make(TransactionCategoryCriterion::value)
                                ->label(fn(Get $get): string => $get(TransactionCategoryCriterion::operator) === TransactionCategoryCriterion::OP_BETWEEN
                                    ? 'Von (Mindestwert)'
                                    : 'Wert')
                                ->required(),

                            TextInput::make(TransactionCategoryCriterion::value_secondary)
                                ->label('Bis (Maximalwert)')
                                ->visible(fn(Get $get): bool => $get(TransactionCategoryCriterion::operator) === TransactionCategoryCriterion::OP_BETWEEN)
                                ->required(fn(Get $get): bool => $get(TransactionCategoryCriterion::operator) === TransactionCategoryCriterion::OP_BETWEEN),

                            Toggle::make(TransactionCategoryCriterion::case_sensitive)
                                ->label('Groß-/Kleinschreibung beachten')
                                ->default(false)
                                ->visible(fn(Get $get): bool => in_array(
                                    $get(TransactionCategoryCriterion::operator),
                                    TransactionCategoryCriterion::getTextOperators(),
                                    true
                                )),
                        ])
                        ->columns(2),
                ])
                ->columns(1),
        ]);
    }
}
