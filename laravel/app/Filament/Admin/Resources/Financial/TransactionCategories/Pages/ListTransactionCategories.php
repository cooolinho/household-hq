<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\Actions\CreateSubcategoryAction;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Actions\MoveCategoryAction;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Actions\ViewCategoryTransactionsAction;
use App\Filament\Admin\Resources\Financial\TransactionCategories\Support\TransactionCategoryBreadcrumbs;
use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListTransactionCategories extends ListRecords
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
            ->recordUrl(fn(TransactionCategory $record): string => static::getResource()::getUrl('index', [
                'filters' => [
                    TransactionCategory::parent_id => [
                        'value' => $record->{TransactionCategory::id},
                    ],
                ],
            ]));
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
