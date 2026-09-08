<?php

namespace App\Filament\App\Clusters\Settings\Pages;

use App\Filament\App\Clusters\Settings\SettingsCluster;
use App\Models\CustomDashboardUserWidget;
use App\Models\DashboardWidgetPreference;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplateRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use InvalidArgumentException;

class DashboardSettingsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Dashboard';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-squares-2x2';

    public ?array $data = [];

    protected string $view = 'filament.app.clusters.settings.pages.dashboard-settings-page';

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
            DashboardWidgetPreference::include_budgets_in_balance,
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
                        Toggle::make(DashboardWidgetPreference::include_budgets_in_balance)
                            ->label('Budgets in die Fixkosten-Bilanz einrechnen')
                            ->helperText('Alle aktiven Budgets mit der Option "In Fixkosten-Bilanz einrechnen" werden als eine zusammengefasste Ausgabe gezählt.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $userId = auth()->id();
        $query = CustomDashboardUserWidget::query();

        if ($userId) {
            $query->where(CustomDashboardUserWidget::user_id, $userId);
        } else {
            $query->whereKey(0);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make(CustomDashboardUserWidget::title)
                    ->label('Titel')
                    ->searchable()
                    ->limit(35),
                TextColumn::make(CustomDashboardUserWidget::navigation_group)
                    ->label('Bereich')
                    ->formatStateUsing(
                        fn(string $state): string => $this->widgetRegistry()->groupLabel($state) ?? $state,
                    )
                    ->sortable(),
                TextColumn::make(CustomDashboardUserWidget::template_key)
                    ->label('Vorlage')
                    ->formatStateUsing(
                        fn(string $state, CustomDashboardUserWidget $record): string => $this->widgetRegistry()
                            ->find((string)$record->{CustomDashboardUserWidget::navigation_group}, $state)?->label ?? $state,
                    ),
                TextColumn::make(CustomDashboardUserWidget::widget_type)
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(
                        fn(mixed $state): string => $state instanceof \BackedEnum ? (string)$state->value : (string)$state,
                    ),
                TextColumn::make(CustomDashboardUserWidget::width)
                    ->label('Breite'),
                TextColumn::make(CustomDashboardUserWidget::sort)
                    ->label('Sort')
                    ->numeric()
                    ->sortable(),
                IconColumn::make(CustomDashboardUserWidget::is_active)
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->headerActions([
                $this->createCustomWidgetAction(),
            ])
            ->recordActions([
                $this->editCustomWidgetAction(),
                $this->deleteCustomWidgetAction(),
            ])
            ->defaultSort(CustomDashboardUserWidget::sort);
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
            DashboardWidgetPreference::include_budgets_in_balance => (bool)($state[DashboardWidgetPreference::include_budgets_in_balance] ?? true),
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

    /**
     * @return array<int, Component>
     */
    private function customWidgetFormSchema(): array
    {
        return [
            Section::make('Allgemein')
                ->columns(2)
                ->schema([
                    TextInput::make(CustomDashboardUserWidget::title)
                        ->label('Titel')
                        ->maxLength(120)
                        ->required(),
                    Select::make(CustomDashboardUserWidget::navigation_group)
                        ->label('Bereich')
                        ->options(fn(): array => $this->widgetRegistry()->groupOptions())
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set(CustomDashboardUserWidget::template_key, null);
                            $set(CustomDashboardUserWidget::configuration, []);
                        })
                        ->required(),
                    Select::make(CustomDashboardUserWidget::template_key)
                        ->label('Widget-Vorlage')
                        ->options(
                            fn(Get $get): array => $this->widgetRegistry()->templateOptions(
                                (string)$get(CustomDashboardUserWidget::navigation_group),
                            ),
                        )
                        ->disabled(
                            fn(Get $get): bool => blank($get(CustomDashboardUserWidget::navigation_group)),
                        )
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            if (blank($state)) {
                                $set(CustomDashboardUserWidget::configuration, []);

                                return;
                            }

                            $set(
                                CustomDashboardUserWidget::configuration,
                                $this->widgetRegistry()->defaultConfiguration(
                                    (string)$get(CustomDashboardUserWidget::navigation_group),
                                    $state,
                                ),
                            );
                        })
                        ->required(),
                    Select::make(CustomDashboardUserWidget::width)
                        ->label('Breite')
                        ->options([
                            CustomDashboardUserWidget::WIDTH_SMALL => '1 Spalte',
                            CustomDashboardUserWidget::WIDTH_HALF => '2 Spalten',
                            CustomDashboardUserWidget::WIDTH_FULL_GRID => '4 Spalten',
                            CustomDashboardUserWidget::WIDTH_FULL => 'Volle Breite',
                        ])
                        ->default(CustomDashboardUserWidget::WIDTH_FULL)
                        ->required(),
                    TextInput::make(CustomDashboardUserWidget::sort)
                        ->label('Sortierung')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make(CustomDashboardUserWidget::is_active)
                        ->label('Widget aktiv')
                        ->default(true),
                ]),
            Section::make('Vorlagen-Konfiguration')
                ->columns(2)
                ->schema(
                    fn(Get $get): array => $this->widgetRegistry()->configurationSchema(
                        (string)$get(CustomDashboardUserWidget::navigation_group),
                        (string)$get(CustomDashboardUserWidget::template_key),
                    ),
                )
                ->statePath(CustomDashboardUserWidget::configuration)
                ->visible(
                    fn(Get $get): bool => filled($get(CustomDashboardUserWidget::template_key)),
                ),
        ];
    }

    private function createCustomWidgetAction(): Action
    {
        return Action::make('createCustomWidget')
            ->label('Eigenes Widget hinzufügen')
            ->form($this->customWidgetFormSchema())
            ->action(function (array $data): void {
                CustomDashboardUserWidget::query()->create($this->prepareCustomWidgetData($data));

                Notification::make()
                    ->success()
                    ->title('Eigenes Dashboard-Widget angelegt.')
                    ->send();
            });
    }

    private function editCustomWidgetAction(): EditAction
    {
        return EditAction::make('editCustomWidget')
            ->label('Bearbeiten')
            ->form($this->customWidgetFormSchema())
            ->authorize(
                fn(CustomDashboardUserWidget $record): bool => $this->ownsCustomWidget($record),
            )
            ->using(function (array $data, CustomDashboardUserWidget $record): CustomDashboardUserWidget {
                $record->update($this->prepareCustomWidgetData($data));

                return $record;
            });
    }

    private function deleteCustomWidgetAction(): DeleteAction
    {
        return DeleteAction::make('deleteCustomWidget')
            ->label('Löschen')
            ->authorize(
                fn(CustomDashboardUserWidget $record): bool => $this->ownsCustomWidget($record),
            );
    }

    private function widgetRegistry(): CustomDashboardWidgetTemplateRegistry
    {
        return app(CustomDashboardWidgetTemplateRegistry::class);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareCustomWidgetData(array $data): array
    {
        $userId = auth()->id();

        if (!$userId) {
            abort(403);
        }

        $group = trim((string)($data[CustomDashboardUserWidget::navigation_group] ?? ''));
        $templateKey = trim((string)($data[CustomDashboardUserWidget::template_key] ?? ''));
        $template = $this->widgetRegistry()->get($group, $templateKey);
        $width = (string)($data[CustomDashboardUserWidget::width] ?? CustomDashboardUserWidget::WIDTH_FULL);

        if (!in_array($width, [
            CustomDashboardUserWidget::WIDTH_SMALL,
            CustomDashboardUserWidget::WIDTH_HALF,
            CustomDashboardUserWidget::WIDTH_FULL_GRID,
            CustomDashboardUserWidget::WIDTH_FULL,
        ], true)) {
            throw new InvalidArgumentException('Die Widget-Breite ist ungültig.');
        }

        $title = trim((string)($data[CustomDashboardUserWidget::title] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Ein Widget-Titel ist erforderlich.');
        }

        return [
            CustomDashboardUserWidget::user_id => $userId,
            CustomDashboardUserWidget::title => $title,
            CustomDashboardUserWidget::navigation_group => $group,
            CustomDashboardUserWidget::template_key => $templateKey,
            CustomDashboardUserWidget::widget_type => $template->type->value,
            CustomDashboardUserWidget::configuration => $template->normalizeConfiguration(
                is_array($data[CustomDashboardUserWidget::configuration] ?? null)
                    ? $data[CustomDashboardUserWidget::configuration]
                    : [],
            ),
            CustomDashboardUserWidget::width => $width,
            CustomDashboardUserWidget::sort => max(
                0,
                (int)($data[CustomDashboardUserWidget::sort] ?? 0),
            ),
            CustomDashboardUserWidget::is_active => (bool)($data[CustomDashboardUserWidget::is_active] ?? false),
        ];
    }

    private function ownsCustomWidget(?CustomDashboardUserWidget $widget): bool
    {
        return $widget !== null
            && auth()->id() !== null
            && (int)$widget->{CustomDashboardUserWidget::user_id} === (int)auth()->id();
    }
}
