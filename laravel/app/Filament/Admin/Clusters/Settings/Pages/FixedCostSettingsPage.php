<?php

namespace App\Filament\Admin\Clusters\Settings\Pages;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\FixedCostSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FixedCostSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Fixkosten';
    protected static ?string $cluster = SettingsCluster::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-credit-card';
    public ?array $data = [];
    protected string $view = 'filament.admin.clusters.settings.pages.fixed-cost-settings-page';

    public static function getNavigationLabel(): string
    {
        return 'Fixkosten';
    }

    public function mount(FixedCostSettings $settings): void
    {
        $this->form->fill([
            'update_schedule_time' => $settings->update_schedule_time,
            'reminders_enabled' => $settings->reminders_enabled,
            'reminders_schedule_time' => $settings->reminders_schedule_time,
            'matching_threshold' => $settings->matching_threshold,
            'matching_schedule_time' => $settings->matching_schedule_time,
            'matching_enabled' => $settings->matching_enabled,
            'matching_learning_enabled' => $settings->matching_learning_enabled,
            'matching_learning_auto_learn_min_score' => $settings->matching_learning_auto_learn_min_score,
            'matching_learning_auto_positive_weight' => $settings->matching_learning_auto_positive_weight,
            'matching_learning_accepted_positive_weight' => $settings->matching_learning_accepted_positive_weight,
            'matching_learning_rejected_negative_weight' => $settings->matching_learning_rejected_negative_weight,
            'matching_learning_reject_block_threshold' => $settings->matching_learning_reject_block_threshold,
            'matching_learning_rule_confidence_min' => $settings->matching_learning_rule_confidence_min,
            'matching_learning_amount_tolerance_percent' => $settings->matching_learning_amount_tolerance_percent,
            'recurring_enabled' => $settings->recurring_enabled,
            'recurring_schedule_time' => $settings->recurring_schedule_time,
            'recurring_min_occurrences' => $settings->recurring_min_occurrences,
            'recurring_window_months' => $settings->recurring_window_months,
            'recurring_amount_tolerance_percent' => $settings->recurring_amount_tolerance_percent,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Jobs')
                    ->columns(3)
                    ->schema([
                        TextInput::make('update_schedule_time')
                            ->label('Update Job (HH:MM)')
                            ->helperText('Tägliche Uhrzeit für den Job, der Fixkosten aktualisiert.')
                            ->required()
                            ->rule('date_format:H:i'),
                        Toggle::make('reminders_enabled')
                            ->label('Reminder aktiviert')
                            ->helperText('Schaltet den globalen Erinnerungsversand für Fixkosten ein oder aus.'),
                        TextInput::make('reminders_schedule_time')
                            ->label('Reminder Job (HH:MM)')
                            ->helperText('Tägliche Uhrzeit, zu der Erinnerungen geprüft und versendet werden.')
                            ->required()
                            ->rule('date_format:H:i'),
                        Toggle::make('matching_enabled')
                            ->label('Matching aktiviert')
                            ->helperText('Aktiviert die automatische Zuordnung von Transaktionen zu Fixkosten.'),
                        TextInput::make('matching_schedule_time')
                            ->label('Matching Job (HH:MM)')
                            ->helperText('Tägliche Uhrzeit für die automatische Matching-Ausführung.')
                            ->required()
                            ->rule('date_format:H:i'),
                        Toggle::make('recurring_enabled')
                            ->label('Recurring aktiviert')
                            ->helperText('Aktiviert die Erkennung wiederkehrender Buchungen als Vorschläge.'),
                        TextInput::make('recurring_schedule_time')
                            ->label('Recurring Job (HH:MM)')
                            ->helperText('Tägliche Uhrzeit für die Erkennung wiederkehrender Buchungen.')
                            ->required()
                            ->rule('date_format:H:i'),
                    ]),
                Section::make('Matching')
                    ->columns(3)
                    ->schema([
                        TextInput::make('matching_threshold')
                            ->label('Auto-Link Threshold')
                            ->helperText('Mindest-Score (0-100) für eine automatische Verknüpfung ohne manuellen Vorschlag.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        Toggle::make('matching_learning_enabled')
                            ->label('Learning aktiviert')
                            ->helperText('Aktiviert lernende Matching-Regeln aus automatischen und manuellen Entscheidungen.'),
                        TextInput::make('matching_learning_auto_learn_min_score')
                            ->label('Auto-Learn Min Score')
                            ->helperText('Ab diesem Score wird ein Auto-Link als positives Lernsignal gewertet.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('matching_learning_auto_positive_weight')
                            ->label('Auto-Learn Gewicht')
                            ->helperText('Gewichtung für positives Lernen aus automatischen Verknüpfungen.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->required(),
                        TextInput::make('matching_learning_accepted_positive_weight')
                            ->label('Accept Gewicht')
                            ->helperText('Gewichtung für positives Lernen bei manuell akzeptierten Vorschlägen.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->required(),
                        TextInput::make('matching_learning_rejected_negative_weight')
                            ->label('Reject Gewicht')
                            ->helperText('Gewichtung für negatives Lernen bei manuell abgelehnten Vorschlägen.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->required(),
                        TextInput::make('matching_learning_reject_block_threshold')
                            ->label('Reject Block Threshold')
                            ->helperText('Ab dieser negativen Gewichtung wird ein Kandidat für Matching blockiert.')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->required(),
                        TextInput::make('matching_learning_rule_confidence_min')
                            ->label('Rule Confidence Min')
                            ->helperText('Mindestvertrauen (0-100), damit eine gelernte Regel direkt verknüpfen darf.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('matching_learning_amount_tolerance_percent')
                            ->label('Learning Betrag-Toleranz (%)')
                            ->helperText('Toleranz in Prozent, in der Beträge für gelernte Regeln als passend gelten.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
                Section::make('Recurring')
                    ->columns(2)
                    ->schema([
                        TextInput::make('recurring_min_occurrences')
                            ->label('Min. Vorkommen')
                            ->helperText('Mindestanzahl ähnlicher Buchungen, bevor ein Vorschlag erzeugt wird.')
                            ->numeric()
                            ->minValue(2)
                            ->required(),
                        TextInput::make('recurring_window_months')
                            ->label('Fenster (Monate)')
                            ->helperText('Wie viele Monate rückwirkend für die Erkennung berücksichtigt werden.')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('recurring_amount_tolerance_percent')
                            ->label('Betrag-Toleranz (%)')
                            ->helperText('Erlaubte prozentuale Betragsabweichung bei wiederkehrenden Buchungen.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(FixedCostSettings $settings): void
    {
        $state = $this->form->getState();

        $settings->update_schedule_time = (string)$state['update_schedule_time'];
        $settings->reminders_enabled = (bool)$state['reminders_enabled'];
        $settings->reminders_schedule_time = (string)$state['reminders_schedule_time'];
        $settings->matching_threshold = (int)$state['matching_threshold'];
        $settings->matching_schedule_time = (string)$state['matching_schedule_time'];
        $settings->matching_enabled = (bool)$state['matching_enabled'];
        $settings->matching_learning_enabled = (bool)$state['matching_learning_enabled'];
        $settings->matching_learning_auto_learn_min_score = (int)$state['matching_learning_auto_learn_min_score'];
        $settings->matching_learning_auto_positive_weight = (float)$state['matching_learning_auto_positive_weight'];
        $settings->matching_learning_accepted_positive_weight = (float)$state['matching_learning_accepted_positive_weight'];
        $settings->matching_learning_rejected_negative_weight = (float)$state['matching_learning_rejected_negative_weight'];
        $settings->matching_learning_reject_block_threshold = (float)$state['matching_learning_reject_block_threshold'];
        $settings->matching_learning_rule_confidence_min = (int)$state['matching_learning_rule_confidence_min'];
        $settings->matching_learning_amount_tolerance_percent = (int)$state['matching_learning_amount_tolerance_percent'];
        $settings->recurring_enabled = (bool)$state['recurring_enabled'];
        $settings->recurring_schedule_time = (string)$state['recurring_schedule_time'];
        $settings->recurring_min_occurrences = (int)$state['recurring_min_occurrences'];
        $settings->recurring_window_months = (int)$state['recurring_window_months'];
        $settings->recurring_amount_tolerance_percent = (int)$state['recurring_amount_tolerance_percent'];
        $settings->save();

        Notification::make()
            ->success()
            ->title('Fixkosten-Einstellungen gespeichert.')
            ->send();
    }
}
