<?php

namespace App\Filament\Admin\Clusters\Settings\Pages;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\ReminderSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReminderSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Erinnerungen';
    protected static ?string $cluster = SettingsCluster::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-bell';
    public ?array $data = [];
    protected string $view = 'filament.admin.clusters.settings.pages.reminder-settings-page';

    public static function getNavigationLabel(): string
    {
        return 'Erinnerungen';
    }

    public function mount(ReminderSettings $settings): void
    {
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Erinnerungs-Job')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Erinnerungen aktiviert')
                            ->columnSpanFull()
                            ->helperText('Schaltet den globalen, stündlichen Erinnerungsversand ein oder aus.'),
                        TextInput::make('default_run_at_time')
                            ->label('Standard-Uhrzeit (HH:MM)')
                            ->helperText('Wird verwendet, wenn ein Intervall keine eigene Uhrzeit hat (nicht relevant bei Stunden-Vorlauf).')
                            ->required()
                            ->rule('date_format:H:i'),
                        TextInput::make('catch_up_hours')
                            ->label('Kulanzfenster (Stunden)')
                            ->helperText('Wie viele Stunden nach dem eigentlichen Fälligkeitszeitpunkt eine verpasste Erinnerung noch nachgeholt wird.')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(ReminderSettings $settings): void
    {
        $state = $this->form->getState();

        $settings->enabled = (bool)$state['enabled'];
        $settings->default_run_at_time = (string)$state['default_run_at_time'];
        $settings->catch_up_hours = (int)$state['catch_up_hours'];
        $settings->save();

        Notification::make()
            ->success()
            ->title('Erinnerungs-Einstellungen gespeichert.')
            ->send();
    }
}
