<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\Actions;

use App\Filament\App\Resources\Financial\TransactionCategories\Pages\ListTransactionCategories;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class SearchCategoriesAction
{
    private const string FIELD_CATEGORY = 'category';

    public static function make(): Action
    {
        return Action::make('searchCategories')
            ->label('Kategorie suchen')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->modalHeading('Kategorie suchen')
            ->modalDescription('Gib den Namen einer Haupt- oder Unterkategorie ein.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
            ->schema([
                Select::make(self::FIELD_CATEGORY)
                    ->label('Kategorie')
                    ->placeholder('Name eingeben...')
                    ->searchable()
                    ->getSearchResultsUsing(fn(string $search): array => self::getSearchResults($search))
                    ->getOptionLabelUsing(fn(mixed $value): ?string => self::getOptionLabel($value))
                    ->live()
                    ->afterStateUpdated(fn(?string $state) => self::redirectToCategory($state))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function getSearchResults(string $search): array
    {
        if (!filled($search)) {
            return [];
        }

        $categoriesById = self::getVisibleCategories()->keyBy(TransactionCategory::id);

        return $categoriesById
            ->filter(fn(TransactionCategory $category): bool => str_contains(
                mb_strtolower($category->{TransactionCategory::name}),
                mb_strtolower($search)
            ))
            ->sortBy(TransactionCategory::name)
            ->mapWithKeys(fn(TransactionCategory $category): array => [
                (string)$category->getKey() => self::buildPath($category, $categoriesById),
            ])
            ->all();
    }

    private static function getOptionLabel(mixed $value): ?string
    {
        $categoriesById = self::getVisibleCategories()->keyBy(TransactionCategory::id);
        $category = $categoriesById->get((int)$value);

        return $category === null ? null : self::buildPath($category, $categoriesById);
    }

    private static function redirectToCategory(?string $state): void
    {
        if ($state === null) {
            return;
        }

        $category = self::getVisibleCategories()->keyBy(TransactionCategory::id)->get((int)$state);

        if ($category === null) {
            return;
        }

        redirect(ListTransactionCategories::getRecordFilterIndexUrl($category));
    }

    /**
     * @return Collection<int, TransactionCategory>
     */
    private static function getVisibleCategories(): Collection
    {
        return TransactionCategory::query()
            ->visibleForUser((int)auth()->id())
            ->get([TransactionCategory::id, TransactionCategory::name, TransactionCategory::parent_id]);
    }

    /**
     * @param Collection<int, TransactionCategory> $categoriesById
     */
    private static function buildPath(TransactionCategory $category, Collection $categoriesById): string
    {
        $path = [];
        $current = $category;
        $guard = 0;

        while ($current !== null && $guard < 20) {
            array_unshift($path, $current->{TransactionCategory::name});
            $parentId = $current->{TransactionCategory::parent_id};
            $current = $parentId === null ? null : $categoriesById->get((int)$parentId);
            $guard++;
        }

        return implode(' > ', $path);
    }
}
