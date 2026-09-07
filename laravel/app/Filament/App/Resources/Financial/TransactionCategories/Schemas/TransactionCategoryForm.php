<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\Schemas;

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
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

            Toggle::make(TransactionCategory::active)
                ->label('Aktiv')
                ->default(true)
                ->disabled(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false),

            self::makeManagedRulesRepeater(),

            self::makeUserExtensionRulesRepeater(),

            self::makeGlobalRulesRepeater(),
        ]);
    }

    /**
     * Zeigt bei geseedeten Systemregeln den lesbar aufbereiteten Key,
     * bei manuell angelegten Regeln die ID.
     *
     * @param array<string, mixed> $state
     */
    private static function globalRuleLabel(array $state): string
    {
        $key = $state['rule_key'] ?? null;

        if (is_string($key) && $key !== '') {
            return 'Systemregel: ' . str_replace(['.', '-'], [' › ', ' '], $key);
        }

        return 'Systemregel #' . ($state['rule_id'] ?? '?');
    }

    private static function makeGlobalRulesRepeater(): Repeater
    {
        return Repeater::make('global_rule_settings')
            ->label('Systemregeln')
            ->columnSpanFull()
            ->collapsed()
            ->helperText('Deaktiviere einzelne globale Regeln nur für deinen Account. Andere User bleiben unverändert.')
            ->itemLabel(fn(array $state): string => self::globalRuleLabel($state))
            ->schema([
                Hidden::make('rule_id'),
                Hidden::make('rule_key'),
                Grid::make([
                    'default' => 1,
                    'lg' => 12,
                ])->schema([
                    Checkbox::make('disabled')
                        ->label('Regel deaktivieren')
                        ->helperText('Nur für dich deaktiviert.')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    ViewField::make('preview')
                        ->hiddenLabel()
                        ->view(
                            'filament.app.resources.financial.transaction-categories.actions.system-rule-preview',
                            fn(Get $get): array => [
                                'preview' => $get('preview') ?? [],
                            ],
                        )
                        ->dehydrated(false)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 9,
                        ]),
                ]),
            ])
            ->columns(1)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->visible(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false)
            ->dehydrated(fn(?TransactionCategory $record): bool => $record?->isGlobal() ?? false);
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
}
