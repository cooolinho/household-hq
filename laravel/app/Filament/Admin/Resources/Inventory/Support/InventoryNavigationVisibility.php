<?php

namespace App\Filament\Admin\Resources\Inventory\Support;

use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Illuminate\Database\Eloquent\Builder;

class InventoryNavigationVisibility
{
    public static function hasArticlesForCurrentUser(): bool
    {
        $userId = auth()->id();

        if ($userId === null) {
            return false;
        }

        return Article::query()
            ->whereHas(
                Article::belongs_to_location . '.' . Location::belongs_to_collection,
                fn(Builder $query) => $query->where(Collection::user_id, $userId)
            )
            ->exists();
    }
}
