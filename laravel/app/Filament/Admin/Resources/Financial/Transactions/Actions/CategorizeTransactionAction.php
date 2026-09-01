<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Actions;

use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CategorizeTransactionAction
{
    public static function make(): Action
    {
        return Action::make('categorizeTransaction')
            ->label('Kategorisieren')
            ->icon(Heroicon::OutlinedTag)
            ->schema(self::form())
            ->fillForm(self::fillForm())
            ->modalHeading('Transaktion kategorisieren')
            ->modalDescription(fn(Transaction $record): HtmlString => new HtmlString(
                view('filament.admin.resources.financial.transactions.actions.transaction-statement', [
                    'record' => $record,
                ])->render()
            ))
            ->modalWidth(Width::ExtraLarge)
            ->action(self::action());
    }

    private static function action(): \Closure
    {
        return function (Transaction $record, array $data) {
            $categoryIds = self::getScopedCategoriesQuery((int)$record->user_id)
                ->whereIn(TransactionCategory::id, $data['categories'] ?? [])
                ->pluck(TransactionCategory::id)
                ->toArray();

            // Pivot-Tabelle hat nur created_at, kein updated_at — deshalb syncWithoutDetaching
            // nicht nutzbar; stattdessen manuell detach + attach mit created_at
            $existing = $record->transactionCategories()->pluck('id')->toArray();
            $toDetach = array_diff($existing, $categoryIds);
            $toAttach = array_diff($categoryIds, $existing);

            if (!empty($toDetach)) {
                $record->transactionCategories()->detach($toDetach);
            }

            if (!empty($toAttach)) {
                $record->transactionCategories()->attach(
                    array_fill_keys($toAttach, ['created_at' => now()])
                );
            }

            Notification::make()
                ->title('Kategorien gespeichert')
                ->success()
                ->send();
        };
    }

    private static function fillForm(): \Closure
    {
        return function (Transaction $record): array {
            return [
                'categories' => $record->transactionCategories()->pluck('id')->toArray(),
            ];
        };
    }

    private static function form(): array
    {
        return [
            Select::make('categories')
                ->label('Kategorien')
                ->multiple()
                ->options(fn() => self::getCategoryOptions())
                ->searchable()
                ->placeholder('Kategorie auswählen...'),
        ];
    }

    private static function getCategoryOptions(): array
    {
        return self::getScopedCategoriesQuery((int)auth()->id())
            ->with(TransactionCategory::belongs_to_parent)
            ->get()
            ->mapWithKeys(fn(TransactionCategory $category) => [
                $category->id => $category->getFullNameAttribute(),
            ])
            ->toArray();
    }

    private static function getScopedCategoriesQuery(int $userId): Builder
    {
        return TransactionCategory::query()
            ->where(TransactionCategory::active, true)
            ->visibleForUser($userId);
    }
}
