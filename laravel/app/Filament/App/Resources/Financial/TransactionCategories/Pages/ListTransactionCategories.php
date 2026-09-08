<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\Pages;

use App\Filament\App\Resources\Financial\TransactionCategories\Actions\CreateSubcategoryAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Actions\MoveCategoryAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Actions\SearchCategoriesAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Actions\ViewCategoryTransactionsAction;
use App\Filament\App\Resources\Financial\TransactionCategories\Support\TransactionCategoryBreadcrumbs;
use App\Filament\App\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Jobs\Financial\CategorizeUncategorizedTransactionsJob;
use App\Jobs\Financial\RecategorizeAllTransactionsJob;
use App\Jobs\Financial\RecategorizeCategorizedTransactionsJob;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListTransactionCategories extends ListRecords
{
    private const string FIELD_MODE = 'mode';

    private const string MODE_RECATEGORIZE_CATEGORIZED = 'recategorize_categorized';

    private const string MODE_CATEGORIZE_UNCATEGORIZED = 'categorize_uncategorized';

    private const string MODE_RECATEGORIZE_ALL = 'recategorize_all';

    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SearchCategoriesAction::make(),
            Action::make('categorizeTransactions')
                ->label('Transaktionen kategorisieren')
                ->icon(Heroicon::OutlinedTag)
                ->modalHeading('Transaktionen kategorisieren')
                ->modalDescription('Wähle aus, wie die Transaktionen des angemeldeten Benutzers verarbeitet werden sollen.')
                ->modalSubmitActionLabel('Starten')
                ->schema([
                    Select::make(self::FIELD_MODE)
                        ->label('Vorgehen')
                        ->options([
                            self::MODE_RECATEGORIZE_CATEGORIZED => 'Bereits kategorisierte Transaktionen erneut prüfen und passende Kategorien ergänzen',
                            self::MODE_CATEGORIZE_UNCATEGORIZED => 'Nur noch nicht kategorisierten Transaktionen Kategorien zuweisen',
                            self::MODE_RECATEGORIZE_ALL => 'Alle Kategorien entfernen und alle Transaktionen neu kategorisieren',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $userId = auth()->id();
                    $mode = $data[self::FIELD_MODE] ?? null;

                    if ($userId === null) {
                        Notification::make()
                            ->title('Kategorisierung konnte nicht gestartet werden.')
                            ->danger()
                            ->send();

                        return;
                    }

                    switch ($mode) {
                        case self::MODE_RECATEGORIZE_CATEGORIZED:
                            RecategorizeCategorizedTransactionsJob::dispatch((int)$userId);
                            break;
                        case self::MODE_CATEGORIZE_UNCATEGORIZED:
                            CategorizeUncategorizedTransactionsJob::dispatch((int)$userId);
                            break;
                        case self::MODE_RECATEGORIZE_ALL:
                            RecategorizeAllTransactionsJob::dispatch((int)$userId);
                            break;
                        default:
                            Notification::make()
                                ->title('Bitte ein gültiges Vorgehen auswählen.')
                                ->danger()
                                ->send();

                            return;
                    }

                    Notification::make()
                        ->title('Kategorisierung gestartet')
                        ->body('Die Transaktionen werden im Hintergrund verarbeitet.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Hauptkategorie erstellen')
                ->visible(fn(): bool => $this->getCurrentParentCategory() === null),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn(TransactionCategory $record): bool => !$record->isGlobal()),
                ViewCategoryTransactionsAction::make(),
                CreateSubcategoryAction::make(),
                MoveCategoryAction::make(),
            ])
                ->record(fn(): ?TransactionCategory => $this->getCurrentParentCategory())
                ->visible(fn(): bool => $this->getCurrentParentCategory() !== null),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->where(
                TransactionCategory::parent_id,
                $this->getCurrentParentId()
            ))
            ->recordUrl(fn(TransactionCategory $record): string => self::getRecordFilterIndexUrl($record));
    }

    public static function getRecordFilterIndexUrl(TransactionCategory $record)
    {
        return static::getResource()::getUrl('index', [
            'filters' => [
                TransactionCategory::parent_id => [
                    'value' => $record->{TransactionCategory::id},
                ],
            ],
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return TransactionCategoryBreadcrumbs::forList($this->getCurrentParentId());
    }

    public function getCurrentParentCategory(): ?TransactionCategory
    {
        $parentId = $this->getCurrentParentId();

        if ($parentId === null) {
            return null;
        }

        return TransactionCategory::query()
            ->visibleForUser((int)auth()->id())
            ->find($parentId);
    }

    public function getCurrentParentId(): ?int
    {
        $value = $this->tableFilters[TransactionCategory::parent_id]['value'] ?? null;

        return is_numeric($value) ? (int)$value : null;
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->getCurrentParentCategory() === null) {
            return 'Hauptkategorien';
        }

        return $this->getCurrentParentCategory()?->{TransactionCategory::name} ?? 'Hauptkategorien';
    }
}
