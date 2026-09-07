<?php

namespace App\Filament\App\Resources\Financial\TransactionCategories\Support;

use App\Filament\App\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;

class TransactionCategoryBreadcrumbs
{
    /**
     * @return array<string|int, string>
     */
    public static function forList(?int $parentId): array
    {
        return self::build($parentId);
    }

    /**
     * @return array<string|int, string>
     */
    private static function build(?int $currentCategoryId): array
    {
        $breadcrumbs = [
            self::getLevelUrl() => 'Hauptkategorien',
        ];
        $chain = [];
        $parentId = $currentCategoryId;
        $guard = 0;

        while ($parentId !== null && $guard < 20) {
            $category = TransactionCategory::query()
                ->visibleForUser((int)auth()->id())
                ->find($parentId);

            if ($category === null) {
                break;
            }

            $chain[] = $category;
            $parentId = $category->{TransactionCategory::parent_id};
            $guard++;
        }

        $chain = array_reverse($chain);

        foreach ($chain as $index => $category) {
            if ($index === array_key_last($chain)) {
                $breadcrumbs[] = $category->{TransactionCategory::name};

                continue;
            }

            $breadcrumbs[self::getLevelUrl((int)$category->{TransactionCategory::id})] = $category->{TransactionCategory::name};
        }

        return $breadcrumbs;
    }

    private static function getLevelUrl(?int $parentId = null): string
    {
        if ($parentId === null) {
            return TransactionCategoryResource::getUrl('index');
        }

        return TransactionCategoryResource::getUrl('index', [
            'filters' => [
                TransactionCategory::parent_id => [
                    'value' => $parentId,
                ],
            ],
        ]);
    }

    /**
     * @return array<string|int, string>
     */
    public static function forEdit(TransactionCategory $category): array
    {
        return [
            ...self::build($category->getKey()),
            'Bearbeiten',
        ];
    }
}
