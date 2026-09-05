<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use Filament\Forms\Components\Toggle;

final class InventoryDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'inventory-counts',
                label: 'Inventarbestand',
                type: CustomDashboardWidgetTypeEnum::STAT,
                configurationKeys: ['include_archived'],
                configurationSchema: fn(): array => [
                    Toggle::make('include_archived')
                        ->label('Archivierte Artikel einbeziehen')
                        ->default(false),
                ],
                defaultConfiguration: fn(): array => ['include_archived' => false],
                dataResolver: function (int $userId, array $configuration): array {
                    $articles = Article::query()
                        ->whereHas(
                            Article::belongs_to_location . '.' . Location::belongs_to_collection,
                            fn($query) => $query->where(Collection::user_id, $userId),
                        )
                        ->when(
                            !((bool)$configuration['include_archived']),
                            fn($query) => $query->where(Article::archived, false),
                        )
                        ->count();
                    $locations = Location::query()
                        ->whereHas(
                            Location::belongs_to_collection,
                            fn($query) => $query->where(Collection::user_id, $userId),
                        )
                        ->count();
                    $collections = Collection::query()
                        ->where(Collection::user_id, $userId)
                        ->count();

                    return [
                        [
                            'label' => 'Artikel',
                            'value' => (string)$articles,
                            'description' => 'Im eigenen Inventar',
                            'color' => 'primary',
                        ],
                        [
                            'label' => 'Standorte',
                            'value' => (string)$locations,
                            'description' => 'Über alle Sammlungen',
                            'color' => 'info',
                        ],
                        [
                            'label' => 'Sammlungen',
                            'value' => (string)$collections,
                            'description' => 'Eigene Inventarsammlungen',
                            'color' => 'success',
                        ],
                    ];
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'include_archived' => (bool)$configuration['include_archived'],
                ],
            ),
        ];
    }
}
