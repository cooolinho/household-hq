<?php

namespace App\Services\Dashboard\Widgets\Templates;

use App\Models\Document;
use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use App\Services\Dashboard\Widgets\CustomDashboardWidgetTemplate;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FeaturesDashboardWidgetTemplates
{
    /**
     * @return array<int, CustomDashboardWidgetTemplate>
     */
    public static function templates(): array
    {
        return [
            new CustomDashboardWidgetTemplate(
                key: 'recent-documents',
                label: 'Neueste Dokumente',
                type: CustomDashboardWidgetTypeEnum::TABLE,
                configurationKeys: ['limit'],
                configurationSchema: fn(): array => [
                    TextInput::make('limit')
                        ->label('Maximale Zeilen')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(25)
                        ->default(10)
                        ->required(),
                ],
                defaultConfiguration: fn(): array => ['limit' => 10],
                dataResolver: fn(int $userId, array $configuration): array => [],
                tableConfigurator: function (Table $table, int $userId, array $configuration): Table {
                    $limit = max(1, min(25, (int)$configuration['limit']));

                    return $table
                        ->query(
                            Document::query()
                                ->where(Document::user_id, $userId)
                                ->latest(Document::created_at)
                                ->limit($limit),
                        )
                        ->paginated(false)
                        ->columns([
                            TextColumn::make(Document::filename)
                                ->label('Datei')
                                ->limit(40)
                                ->placeholder('-'),
                            TextColumn::make(Document::type)
                                ->label('Typ')
                                ->badge()
                                ->placeholder('-'),
                            TextColumn::make(Document::created_at)
                                ->label('Erstellt')
                                ->dateTime('d.m.Y H:i'),
                        ]);
                },
                configurationNormalizer: fn(array $configuration): array => [
                    'limit' => max(1, min(25, (int)$configuration['limit'])),
                ],
            ),
        ];
    }
}
