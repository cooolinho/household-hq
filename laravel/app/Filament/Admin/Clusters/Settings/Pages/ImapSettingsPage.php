<?php

namespace App\Filament\Admin\Clusters\Settings\Pages;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\ImapImportSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImapSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'IMAP Import';
    protected static ?string $cluster = SettingsCluster::class;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-envelope';
    public ?array $data = [];
    protected string $view = 'filament.admin.clusters.settings.pages.imap-settings-page';

    public static function getNavigationLabel(): string
    {
        return 'IMAP Import';
    }

    public function mount(ImapImportSettings $settings): void
    {
        $this->form->fill([
            'enabled' => $settings->enabled,
            'schedule_minutes' => $settings->schedule_minutes,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('IMAP-Import')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Import aktiviert')
                            ->helperText('Aktiviert oder deaktiviert den automatischen IMAP-Dokumentimport global.'),
                        TextInput::make('schedule_minutes')
                            ->label('Intervall (Minuten)')
                            ->helperText('Legt fest, in welchem Minuten-Intervall der IMAP-Import läuft (1-59).')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(59)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(ImapImportSettings $settings): void
    {
        $state = $this->form->getState();

        $settings->enabled = (bool)$state['enabled'];
        $settings->schedule_minutes = (int)$state['schedule_minutes'];
        $settings->save();

        Notification::make()
            ->success()
            ->title('IMAP-Einstellungen gespeichert.')
            ->send();
    }
}
