<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Inventory\Articles\ArticleResource;
use App\Filament\Admin\Resources\Inventory\Support\InventoryNavigationVisibility;
use App\Menu\NavigationGroup;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class InventorySetupPage extends Page
{
    protected static ?string $title = 'Inventar einrichten';
    protected static ?string $navigationLabel = 'Inventar starten';
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::INVENTORY;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;
    protected static ?int $navigationSort = 5;

    public string $collectionName = '';
    public string $locationName = '';
    public ?string $locationDescription = null;
    public string $articleName = '';
    public ?string $articleDescription = null;
    public ?int $articleAmount = null;
    public int $collectionsCount = 0;

    protected string $view = 'filament.admin.pages.inventory-setup-page';

    public static function getNavigationLabel(): string
    {
        return 'Inventar starten';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !InventoryNavigationVisibility::hasArticlesForCurrentUser();
    }

    public function mount(): void
    {
        $this->refreshCollectionsCount();
    }

    private function refreshCollectionsCount(): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            $this->collectionsCount = 0;
            return;
        }

        $this->collectionsCount = Collection::query()
            ->where(Collection::user_id, $userId)
            ->count();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Collection')
                        ->description('Lege zuerst eine Sammlung für dein Inventar an.')
                        ->schema([
                            TextInput::make('collectionName')
                                ->label('Name der Collection')
                                ->required()
                                ->maxLength(255),
                        ]),
                    Step::make('Standort')
                        ->description('Erfasse einen ersten Standort innerhalb der Collection.')
                        ->schema([
                            TextInput::make('locationName')
                                ->label('Name des Standorts')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('locationDescription')
                                ->label('Beschreibung')
                                ->rows(3)
                                ->maxLength(1000),
                        ]),
                    Step::make('Artikel')
                        ->description('Lege den ersten Artikel für den Standort an.')
                        ->schema([
                            TextInput::make('articleName')
                                ->label('Artikelname')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('articleDescription')
                                ->label('Beschreibung')
                                ->rows(3)
                                ->maxLength(2000),
                            TextInput::make('articleAmount')
                                ->label('Anzahl')
                                ->numeric()
                                ->minValue(1),
                        ]),
                ])
                    ->persistStepInQueryString('inventorySetupStep')
                    ->submitAction(
                        Action::make('submit')
                            ->label('Inventar-Struktur anlegen')
                            ->icon(Heroicon::OutlinedCheckCircle)
                            ->color('primary')
                            ->button()
                            ->action('submit')
                    ),
            ]);
    }

    public function submit(): void
    {
        $this->validate([
            'collectionName' => ['required', 'string', 'max:255'],
            'locationName' => ['required', 'string', 'max:255'],
            'locationDescription' => ['nullable', 'string', 'max:1000'],
            'articleName' => ['required', 'string', 'max:255'],
            'articleDescription' => ['nullable', 'string', 'max:2000'],
            'articleAmount' => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = auth()->id();
        abort_if($userId === null, 403);

        $article = DB::transaction(function () use ($userId): Article {
            $collection = Collection::query()->create([
                Collection::user_id => $userId,
                Collection::name => trim($this->collectionName),
            ]);

            $location = Location::query()->create([
                Location::collection_id => $collection->id,
                Location::name => trim($this->locationName),
                Location::description => filled($this->locationDescription) ? trim($this->locationDescription) : null,
            ]);

            return Article::query()->create([
                Article::location_id => $location->id,
                Article::name => trim($this->articleName),
                Article::description => filled($this->articleDescription) ? trim($this->articleDescription) : null,
                Article::amount => $this->articleAmount,
            ]);
        });

        $this->reset([
            'collectionName',
            'locationName',
            'locationDescription',
            'articleName',
            'articleDescription',
            'articleAmount',
        ]);

        $this->refreshCollectionsCount();

        Notification::make()
            ->title('Inventar wurde erfolgreich angelegt.')
            ->success()
            ->send();

        $this->redirect(ArticleResource::getUrl('view', ['record' => $article]));
    }

    public function getHasCollectionsProperty(): bool
    {
        return $this->collectionsCount > 0;
    }
}
