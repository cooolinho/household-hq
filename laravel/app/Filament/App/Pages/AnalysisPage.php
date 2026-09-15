<?php

namespace App\Filament\App\Pages;

use App\Menu\NavigationGroup;
use App\Models\AnalysisCard;
use App\Models\Enums\AnalysisModuleEnum;
use App\Services\Analysis\AnalysisModuleRegistry;
use App\Services\Analysis\DTO\AnalysisCardData;
use App\Services\Analysis\DTO\AnalysisDetailData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Zentrale Auswertungs-Seite: konfigurierbare Karten-Grid mit 6 Modulen.
 *
 * CRUD und Detailansicht laufen über Filament-Actions (Modals).
 * Die Karten selbst werden in der Blade-View gerendert und rufen
 * die Actions über wire:click / $this->mountAction auf.
 */
class AnalysisPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::BANKS;

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.app.pages.analysis-page';

    public static function getNavigationLabel(): string
    {
        return 'Auswertung';
    }

    public function getTitle(): string
    {
        return 'Auswertung';
    }

    /**
     * @return Collection<int, AnalysisCard>
     */
    public function getCards(): Collection
    {
        $userId = Auth::id();

        if ($userId === null) {
            return new Collection;
        }

        return AnalysisCard::query()
            ->activeForUser((int) $userId)
            ->get();
    }

    public function getCardData(AnalysisCard $card): AnalysisCardData
    {
        return $this->registry()
            ->calculatorFor($card->{AnalysisCard::module})
            ->card($card);
    }

    public function getDetailData(AnalysisCard $card): AnalysisDetailData
    {
        return $this->registry()
            ->calculatorFor($card->{AnalysisCard::module})
            ->detail($card);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->createCardAction(),
        ];
    }

    /**
     * Per-card actions are resolved by Filament via the `{name}Action` convention
     * when mounted from the Blade grid (`wire:click="mountAction('editCard', …)"`).
     */
    public function createCardAction(): Action
    {
        return Action::make('createCard')
            ->label('Karte hinzufügen')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Neue Auswertungs-Karte')
            ->modalSubmitActionLabel('Anlegen')
            ->schema($this->cardFormSchema())
            ->action(function (array $data): void {
                $userId = Auth::id();

                if ($userId === null) {
                    abort(403);
                }

                AnalysisCard::query()->create($this->prepareCardData($data, (int) $userId));

                Notification::make()
                    ->success()
                    ->title('Auswertungs-Karte angelegt.')
                    ->send();
            });
    }

    public function editCardAction(): Action
    {
        return Action::make('editCard')
            ->label('Bearbeiten')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Auswertungs-Karte bearbeiten')
            ->modalSubmitActionLabel('Speichern')
            ->fillForm(function (array $arguments): array {
                $card = $this->findOwnedCard((int) ($arguments['cardId'] ?? 0));

                return [
                    AnalysisCard::title => $card->{AnalysisCard::title},
                    AnalysisCard::module => $card->{AnalysisCard::module}->name,
                    AnalysisCard::width => $card->{AnalysisCard::width},
                    AnalysisCard::sort => $card->{AnalysisCard::sort},
                    AnalysisCard::is_active => $card->{AnalysisCard::is_active},
                    AnalysisCard::configuration => is_array($card->{AnalysisCard::configuration})
                        ? $card->{AnalysisCard::configuration}
                        : [],
                ];
            })
            ->schema($this->cardFormSchema())
            ->action(function (array $data, array $arguments): void {
                $card = $this->findOwnedCard((int) ($arguments['cardId'] ?? 0));
                $card->update($this->prepareCardData($data, (int) $card->{AnalysisCard::user_id}));

                Notification::make()
                    ->success()
                    ->title('Auswertungs-Karte gespeichert.')
                    ->send();
            });
    }

    public function deleteCardAction(): Action
    {
        return Action::make('deleteCard')
            ->label('Löschen')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Karte löschen?')
            ->modalDescription('Die Auswertungs-Karte wird unwiderruflich gelöscht.')
            ->action(function (array $arguments): void {
                $card = $this->findOwnedCard((int) ($arguments['cardId'] ?? 0));
                $card->delete();

                Notification::make()
                    ->success()
                    ->title('Auswertungs-Karte gelöscht.')
                    ->send();
            });
    }

    public function detailCardAction(): Action
    {
        return Action::make('detailCard')
            ->label('Mehr...')
            ->icon(Heroicon::OutlinedArrowsPointingOut)
            ->modalHeading(fn (array $arguments): string => $this->findOwnedCard((int) ($arguments['cardId'] ?? 0))->{AnalysisCard::title})
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
            ->modalContent(function (array $arguments) {
                $card = $this->findOwnedCard((int) ($arguments['cardId'] ?? 0));
                $detail = $this->getDetailData($card);

                return view('filament.app.partials.analysis-detail-modal', [
                    'card' => $card,
                    'detail' => $detail,
                ]);
            })
            ->modalWidth('5xl');
    }

    public function moveCardUpAction(): Action
    {
        return Action::make('moveCardUp')
            ->label('Nach oben')
            ->icon(Heroicon::OutlinedArrowUp)
            ->action(function (array $arguments): void {
                $this->swapSort((int) ($arguments['cardId'] ?? 0), direction: -1);
            });
    }

    public function moveCardDownAction(): Action
    {
        return Action::make('moveCardDown')
            ->label('Nach unten')
            ->icon(Heroicon::OutlinedArrowDown)
            ->action(function (array $arguments): void {
                $this->swapSort((int) ($arguments['cardId'] ?? 0), direction: 1);
            });
    }

    /**
     * @return array<int, Component>
     */
    private function cardFormSchema(): array
    {
        return [
            Section::make('Allgemein')
                ->columns(2)
                ->schema([
                    TextInput::make(AnalysisCard::title)
                        ->label('Titel')
                        ->maxLength(120)
                        ->required()
                        ->columnSpanFull(),
                    Select::make(AnalysisCard::module)
                        ->label('Modul')
                        ->options(AnalysisModuleEnum::options())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if (blank($state)) {
                                $set(AnalysisCard::configuration, []);

                                return;
                            }

                            $module = AnalysisModuleEnum::tryFrom($state);

                            if ($module === null) {
                                $set(AnalysisCard::configuration, []);

                                return;
                            }

                            $set(AnalysisCard::configuration, $this->registry()->defaults($module));
                        }),
                    Select::make(AnalysisCard::width)
                        ->label('Breite')
                        ->options([
                            AnalysisCard::WIDTH_SMALL => '1 Spalte',
                            AnalysisCard::WIDTH_HALF => '2 Spalten',
                            AnalysisCard::WIDTH_FULL_GRID => '4 Spalten',
                            AnalysisCard::WIDTH_FULL => 'Volle Breite',
                        ])
                        ->default(AnalysisCard::WIDTH_HALF)
                        ->required(),
                    TextInput::make(AnalysisCard::sort)
                        ->label('Sortierung')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make(AnalysisCard::is_active)
                        ->label('Aktiv')
                        ->default(true),
                ]),
            Section::make('Modul-Konfiguration')
                ->columns(2)
                ->schema(fn (Get $get): array => $this->moduleConfigSchema($get(AnalysisCard::module)))
                ->statePath(AnalysisCard::configuration)
                ->visible(fn (Get $get): bool => filled($get(AnalysisCard::module))),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private function moduleConfigSchema(mixed $moduleName): array
    {
        if (blank($moduleName)) {
            return [];
        }

        $module = AnalysisModuleEnum::tryFrom((string) $moduleName);

        if ($module === null) {
            return [];
        }

        return $this->registry()->configSchema($module);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareCardData(array $data, int $userId): array
    {
        $moduleName = (string) ($data[AnalysisCard::module] ?? '');
        $module = AnalysisModuleEnum::tryFrom($moduleName);

        if ($module === null) {
            abort(422, 'Unbekanntes Modul.');
        }

        $title = trim((string) ($data[AnalysisCard::title] ?? ''));

        if ($title === '') {
            abort(422, 'Ein Titel ist erforderlich.');
        }

        $width = (string) ($data[AnalysisCard::width] ?? AnalysisCard::WIDTH_HALF);

        if (! in_array($width, [
            AnalysisCard::WIDTH_SMALL,
            AnalysisCard::WIDTH_HALF,
            AnalysisCard::WIDTH_FULL_GRID,
            AnalysisCard::WIDTH_FULL,
        ], true)) {
            $width = AnalysisCard::WIDTH_HALF;
        }

        $configuration = is_array($data[AnalysisCard::configuration] ?? null)
            ? $data[AnalysisCard::configuration]
            : [];

        return [
            AnalysisCard::user_id => $userId,
            AnalysisCard::title => $title,
            AnalysisCard::module => $module,
            AnalysisCard::configuration => $this->registry()->normalize($module, $configuration),
            AnalysisCard::width => $width,
            AnalysisCard::sort => max(0, (int) ($data[AnalysisCard::sort] ?? 0)),
            AnalysisCard::is_active => (bool) ($data[AnalysisCard::is_active] ?? true),
        ];
    }

    private function findOwnedCard(int $cardId): AnalysisCard
    {
        $userId = Auth::id();

        if ($userId === null || $cardId <= 0) {
            abort(403);
        }

        $card = AnalysisCard::query()
            ->where(AnalysisCard::id, $cardId)
            ->where(AnalysisCard::user_id, $userId)
            ->first();

        if ($card === null) {
            abort(404);
        }

        return $card;
    }

    private function swapSort(int $cardId, int $direction): void
    {
        $card = $this->findOwnedCard($cardId);
        $userId = (int) $card->{AnalysisCard::user_id};

        $neighbor = AnalysisCard::query()
            ->where(AnalysisCard::user_id, $userId)
            ->when(
                $direction < 0,
                fn ($q) => $q
                    ->where(AnalysisCard::sort, '<', $card->{AnalysisCard::sort})
                    ->orderByDesc(AnalysisCard::sort)
                    ->orderByDesc(AnalysisCard::id),
                fn ($q) => $q
                    ->where(AnalysisCard::sort, '>', $card->{AnalysisCard::sort})
                    ->orderBy(AnalysisCard::sort)
                    ->orderBy(AnalysisCard::id),
            )
            ->first();

        // Fallback: wenn sort-Werte gleich sind, nach id sortieren
        if ($neighbor === null) {
            $neighbor = AnalysisCard::query()
                ->where(AnalysisCard::user_id, $userId)
                ->where(AnalysisCard::id, '!=', $card->getKey())
                ->when(
                    $direction < 0,
                    fn ($q) => $q
                        ->where(AnalysisCard::id, '<', $card->getKey())
                        ->orderByDesc(AnalysisCard::id),
                    fn ($q) => $q
                        ->where(AnalysisCard::id, '>', $card->getKey())
                        ->orderBy(AnalysisCard::id),
                )
                ->first();
        }

        if ($neighbor === null) {
            return;
        }

        $cardSort = (int) $card->{AnalysisCard::sort};
        $neighborSort = (int) $neighbor->{AnalysisCard::sort};

        // Bei gleichen Sort-Werten tauschen wir auf cardId-Basis und setzen unterschiedliche Werte.
        if ($cardSort === $neighborSort) {
            if ($direction < 0) {
                $card->{AnalysisCard::sort} = $neighborSort - 1;
            } else {
                $card->{AnalysisCard::sort} = $neighborSort + 1;
            }
            $card->save();

            return;
        }

        $card->{AnalysisCard::sort} = $neighborSort;
        $neighbor->{AnalysisCard::sort} = $cardSort;
        $card->save();
        $neighbor->save();
    }

    private function registry(): AnalysisModuleRegistry
    {
        return app(AnalysisModuleRegistry::class);
    }
}
