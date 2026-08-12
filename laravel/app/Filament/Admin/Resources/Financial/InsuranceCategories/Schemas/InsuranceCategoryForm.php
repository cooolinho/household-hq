<?php

namespace App\Filament\Admin\Resources\Financial\InsuranceCategories\Schemas;

use App\Models\Financial\InsuranceCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InsuranceCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(InsuranceCategory::name)
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Select::make(InsuranceCategory::group)
                    ->label('Gruppe')
                    ->searchable()
                    ->options(function (): array {
                        return InsuranceCategory::query()
                            ->distinct()
                            ->orderBy(InsuranceCategory::group)
                            ->pluck(InsuranceCategory::group, InsuranceCategory::group)
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search): array {
                        $existing = InsuranceCategory::query()
                            ->where(InsuranceCategory::group, 'like', "%{$search}%")
                            ->distinct()
                            ->orderBy(InsuranceCategory::group)
                            ->pluck(InsuranceCategory::group, InsuranceCategory::group)
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

