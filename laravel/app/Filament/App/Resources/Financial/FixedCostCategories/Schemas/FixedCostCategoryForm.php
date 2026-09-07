<?php

namespace App\Filament\App\Resources\Financial\FixedCostCategories\Schemas;

use App\Models\Financial\FixedCostCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FixedCostCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(FixedCostCategory::name)
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Select::make(FixedCostCategory::group)
                    ->label('Gruppe')
                    ->searchable()
                    ->nullable()
                    ->placeholder('Nicht kategorisiert')
                    ->getSearchResultsUsing(function (string $search): array {
                        $existing = FixedCostCategory::query()
                            ->whereNotNull(FixedCostCategory::group)
                            ->where(FixedCostCategory::group, 'like', "%{$search}%")
                            ->distinct()
                            ->orderBy(FixedCostCategory::group)
                            ->pluck(FixedCostCategory::group, FixedCostCategory::group)
                            ->toArray();

                        $trimmed = trim($search);
                        if ($trimmed !== '' && !isset($existing[$trimmed])) {
                            $existing[$trimmed] = $trimmed . ' (neu anlegen)';
                        }

                        return $existing;
                    })
                    ->getOptionLabelUsing(fn(?string $value): string => $value ?? '-'),
            ]);
    }
}

