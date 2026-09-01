<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Schemas;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Filament\Forms\Components\CheckboxList;
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
                ->maxLength(255)
                ->disabled(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false),

            Select::make(TransactionCategory::parent_id)
                ->label('Hauptkategorie')
                ->nullable()
                ->placeholder('Keine (Hauptkategorie)')
                ->searchable()
                ->options(function (?TransactionCategory $record): array {
                    $query = TransactionCategory::query()
                        ->visibleForUser((int)auth()->id())
                        ->whereNull(TransactionCategory::parent_id)
                        ->orderBy(TransactionCategory::name);

                    if ($record?->id) {
                        $query->where(TransactionCategory::id, '!=', $record->id);
                    }

                    return $query->pluck(TransactionCategory::name, TransactionCategory::id)->toArray();
                })
                ->disabled(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false),

            Toggle::make(TransactionCategory::active)
                ->label('Aktiv')
                ->default(true)
                ->disabled(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false),

            self::makeManagedRulesRepeater(),

            self::makeUserExtensionRulesRepeater(),

            CheckboxList::make('disabled_global_rule_ids')
                ->label('Globale Regeln deaktivieren')
                ->helperText('Nur für dich deaktiviert. Andere User behalten die globalen Regeln unverändert.')
                ->options(fn(?TransactionCategory $record): array => self::getGlobalRuleOptions($record))
                ->columns(1)
                ->visible(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false)
                ->dehydrated(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false),
        ]);
    }

    private static function makeManagedRulesRepeater(): Repeater
    {
        return Repeater::make(TransactionCategory::has_many_rules)
            ->label('Regeln')
            ->relationship()
            ->addActionLabel('Regel hinzufügen')
            ->collapsible()
            ->columnSpanFull()
            ->itemLabel(fn(array $state): string => 'Regel (' . ($state[TransactionCategoryRule::operator] ?? 'AND') . ')')
            ->schema(self::getRuleSchema(withRelationshipCriteria: true))
            ->columns(1)
            ->visible(fn(?TransactionCategory $record): bool => $record === null || !$record->isGlobal())
            ->dehydrated(fn(?TransactionCategory $record): bool => $record === null || !$record->isGlobal());
    }

    private static function makeUserExtensionRulesRepeater(): Repeater
    {
        return Repeater::make('user_extension_rules')
            ->label('Eigene Zusatzregeln')
            ->helperText('Diese Regeln ergänzen die globalen Regeln nur für deinen Account.')
            ->addActionLabel('Zusatzregel hinzufügen')
            ->collapsible()
            ->columnSpanFull()
            ->itemLabel(fn(array $state): string => 'Zusatzregel (' . ($state[TransactionCategoryRule::operator] ?? 'AND') . ')')
            ->schema(self::getRuleSchema(withRelationshipCriteria: false))
            ->columns(1)
            ->visible(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false)
            ->dehydrated(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false);
    }

    /**
     * @return array<int, mixed>
     */
    private static function getRuleSchema(bool $withRelationshipCriteria): array
    {
        $criteriaRepeater = Repeater::make(TransactionCategoryRule::has_many_criteria)
            ->label('Kriterien')
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

                        if (in_array($field, TransactionCategoryCriterion::getNumericFields(), true)) {
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
            ->columns(2);

        if ($withRelationshipCriteria) {
            $criteriaRepeater->relationship();
        }

        return [
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

            $criteriaRepeater,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getGlobalRuleOptions(?TransactionCategory $record): array
    {
        if ($record === null || !$record->isGlobal()) {
            return [];
        }

        return $record->rules()
            ->whereNull(TransactionCategoryRule::user_id)
            ->with(TransactionCategoryRule::has_many_criteria)
            ->orderBy(TransactionCategoryRule::id)
            ->get()
            ->mapWithKeys(function (TransactionCategoryRule $rule): array {
                $criteriaSummary = $rule->criteria
                    ->map(function (TransactionCategoryCriterion $criterion): string {
                        return sprintf(
                            '%s %s "%s"',
                            $criterion->field,
                            $criterion->operator,
                            $criterion->value,
                        );
                    })
                    ->implode(' | ');

                $label = sprintf(
                    'Regel #%d (%s)%s',
                    $rule->id,
                    $rule->operator,
                    $criteriaSummary !== '' ? ': ' . $criteriaSummary : '',
                );

                return [(string)$rule->id => $label];
            })
            ->toArray();
    }
}
