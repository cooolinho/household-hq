<?php

namespace App\Filament\Admin\Clusters\Settings\Pages;

use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Models\DashboardWidgetPreference;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DashboardSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Dashboard';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-squares-2x2';
    public ?array $data = [];
    protected string $view = 'filament.admin.clusters.settings.pages.dashboard-settings-page';

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public function mount(): void
    {
        $this->form->fill($this->getPreference()->only([
            DashboardWidgetPreference::show_monthly_balance_stats,
            DashboardWidgetPreference::show_monthly_balance_chart,
            DashboardWidgetPreference::show_upcoming_transactions_table,
            DashboardWidgetPreference::show_portfolio_overview,
            DashboardWidgetPreference::balance_mode,
            DashboardWidgetPreference::currency,
        ]));
    }

    private function getPreference(): DashboardWidgetPreference
    {
        $userId = auth()->id();

        if (!$userId) {
            abort(403);
        }

        return DashboardWidgetPreference::forUser($userId);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Widgets')
                    ->columns(2)
                    ->schema([
                        Toggle::make(DashboardWidgetPreference::show_monthly_balance_stats)
                            ->label('Monatliche Bilanz (Stats)'),
                        Toggle::make(DashboardWidgetPreference::show_monthly_balance_chart)
                            ->label('Bilanz Trend (Chart)'),
                        Toggle::make(DashboardWidgetPreference::show_upcoming_transactions_table)
                            ->label('Letzte Transaktionen (Table)'),
                        Toggle::make(DashboardWidgetPreference::show_portfolio_overview)
                            ->label('Portfolio Uebersicht (Custom)'),
                    ]),
                Section::make('Bilanz und Waehrung')
                    ->columns(2)
                    ->schema([
                        Select::make(DashboardWidgetPreference::balance_mode)
                            ->label('Bilanzmodus')
                            ->options([
                                DashboardWidgetPreference::BALANCE_MODE_BOTH => 'Beides (Prognose + Ist)',
                                DashboardWidgetPreference::BALANCE_MODE_FORECAST => 'Nur Prognose',
                                DashboardWidgetPreference::BALANCE_MODE_ACTUAL => 'Nur Ist',
                            ])
                            ->default(DashboardWidgetPreference::BALANCE_MODE_BOTH)
                            ->required(),
                        TextInput::make(DashboardWidgetPreference::currency)
                            ->label('Bevorzugte Waehrung')
                            ->helperText('Startwert ist EUR. Andere Waehrungen koennen spaeter ebenfalls genutzt werden.')
                            ->default('EUR')
                            ->maxLength(8)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->persistState($this->form->getState(), 'Dashboard-Einstellungen gespeichert.');
    }

    /**
     * @param array<string, mixed> $state
     */
    private function persistState(array $state, string $message): void
    {
        $balanceMode = (string)($state[DashboardWidgetPreference::balance_mode] ?? DashboardWidgetPreference::BALANCE_MODE_BOTH);

        if (!in_array($balanceMode, [
            DashboardWidgetPreference::BALANCE_MODE_BOTH,
            DashboardWidgetPreference::BALANCE_MODE_FORECAST,
            DashboardWidgetPreference::BALANCE_MODE_ACTUAL,
        ], true)) {
            $balanceMode = DashboardWidgetPreference::BALANCE_MODE_BOTH;
        }

        $currency = strtoupper(trim((string)($state[DashboardWidgetPreference::currency] ?? 'EUR')));

        $this->getPreference()->update([
            DashboardWidgetPreference::show_monthly_balance_stats => (bool)($state[DashboardWidgetPreference::show_monthly_balance_stats] ?? true),
            DashboardWidgetPreference::show_monthly_balance_chart => (bool)($state[DashboardWidgetPreference::show_monthly_balance_chart] ?? true),
            DashboardWidgetPreference::show_upcoming_transactions_table => (bool)($state[DashboardWidgetPreference::show_upcoming_transactions_table] ?? true),
            DashboardWidgetPreference::show_portfolio_overview => (bool)($state[DashboardWidgetPreference::show_portfolio_overview] ?? true),
            DashboardWidgetPreference::balance_mode => $balanceMode,
            DashboardWidgetPreference::currency => $currency !== '' ? $currency : 'EUR',
        ]);

        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }

    public function enableAllWidgets(): void
    {
        $state = $this->form->getState();

        foreach ($this->widgetToggleFields() as $field) {
            $state[$field] = true;
        }

        $this->form->fill($state);
        $this->persistState($state, 'Alle Dashboard-Widgets wurden aktiviert.');
    }

    /**
     * @return array<int, string>
     */
    private function widgetToggleFields(): array
    {
        return [
            DashboardWidgetPreference::show_monthly_balance_stats,
            DashboardWidgetPreference::show_monthly_balance_chart,
            DashboardWidgetPreference::show_upcoming_transactions_table,
            DashboardWidgetPreference::show_portfolio_overview,
        ];
    }

    public function disableAllWidgets(): void
    {
        $state = $this->form->getState();

        foreach ($this->widgetToggleFields() as $field) {
            $state[$field] = false;
        }

        $this->form->fill($state);
        $this->persistState($state, 'Alle Dashboard-Widgets wurden deaktiviert.');
    }
}

