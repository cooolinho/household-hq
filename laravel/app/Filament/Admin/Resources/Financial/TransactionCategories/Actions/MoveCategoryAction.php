<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Actions;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MoveCategoryAction
{
    private const string DETACH_PARENT = 'detach_parent';

    public static function make(): Action
    {
        $action = Action::make('moveCategory')
            ->label('Verschieben')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn(TransactionCategory $record): bool => !$record->isGlobal())
            ->schema([
                Select::make(TransactionCategory::parent_id)
                    ->label('Neue übergeordnete Kategorie')
                    ->options(fn(TransactionCategory $record): array => self::getCategoryOptions($record))
                    ->searchable()
                    ->preload()
                    ->required(fn(Get $get): bool => !(bool)$get(self::DETACH_PARENT))
                    ->disabled(fn(Get $get): bool => (bool)$get(self::DETACH_PARENT)),
                Checkbox::make(self::DETACH_PARENT)
                    ->label('Als Hauptkategorie speichern')
                    ->default(false)
                    ->live(),
            ])
            ->modalHeading(fn(TransactionCategory $record): string => 'Kategorie „' . $record->{TransactionCategory::name} . '“ verschieben')
            ->modalSubmitActionLabel('Verschieben');

        $action
            ->extraModalFooterActions(function () use ($action): array {
                return [
                    $action->makeModalSubmitAction('moveAndEdit', arguments: ['mode' => 'edit'])
                        ->label('Verschieben & Bearbeiten'),
                ];
            })
            ->action(function (
                array               $arguments,
                array               $data,
                TransactionCategory $record,
            ): void {
                $record->update([
                    TransactionCategory::parent_id => self::resolveParentId($record, $data),
                ]);

                Notification::make()
                    ->title('Kategorie verschoben')
                    ->success()
                    ->send();

                if (($arguments['mode'] ?? null) === 'edit') {
                    redirect(TransactionCategoryResource::getUrl('edit', [
                        'record' => $record,
                    ]));
                }
            });

        return $action;
    }

    /**
     * @return array<int|string, string>
     */
    private static function getCategoryOptions(TransactionCategory $record): array
    {
        /** @var Collection $categories */
        $categories = TransactionCategory::query()
            ->visibleForUser((int)auth()->id())
            ->whereNotIn(TransactionCategory::id, self::getExcludedCategoryIds($record))
            ->orderBy(TransactionCategory::name)
            ->get([
                TransactionCategory::id,
                TransactionCategory::name,
                TransactionCategory::parent_id,
            ]);
        $categoriesById = $categories->keyBy(TransactionCategory::id);

        return $categories
            ->mapWithKeys(function (TransactionCategory $category) use ($categoriesById): array {
                $path = [];
                $current = $category;
                $guard = 0;

                while ($current !== null && $guard < 20) {
                    array_unshift($path, $current->{TransactionCategory::name});
                    $parentId = $current->{TransactionCategory::parent_id};
                    $current = $parentId === null ? null : $categoriesById->get((int)$parentId);
                    $guard++;
                }

                return [(string)$category->getKey() => implode(' > ', $path)];
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /**
     * @return array<int>
     */
    private static function getExcludedCategoryIds(TransactionCategory $record): array
    {
        return [(int)$record->getKey(), ...$record->getDescendantIds()];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveParentId(TransactionCategory $record, array $data): ?int
    {
        if ((bool)($data[self::DETACH_PARENT] ?? false)) {
            return null;
        }

        $parentId = $data[TransactionCategory::parent_id] ?? null;

        if (filter_var($parentId, FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages([
                TransactionCategory::parent_id => 'Bitte wähle eine gültige Zielkategorie aus.',
            ]);
        }

        $parentId = (int)$parentId;
        $options = self::getCategoryOptions($record);

        if (!array_key_exists((string)$parentId, $options)) {
            throw ValidationException::withMessages([
                TransactionCategory::parent_id => 'Diese Zielkategorie ist nicht zulässig.',
            ]);
        }

        return $parentId;
    }
}
