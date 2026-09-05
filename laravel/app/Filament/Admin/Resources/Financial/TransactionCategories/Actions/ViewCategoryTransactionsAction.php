<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Actions;

use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class ViewCategoryTransactionsAction
{
    public static function make(): Action
    {
        return Action::make('viewCategoryTransactions')
            ->label('Transaktionen')
            ->icon(Heroicon::OutlinedListBullet)
            ->modalHeading(fn(TransactionCategory $record): string => 'Transaktionen: ' . $record->{TransactionCategory::name})
            ->modalContent(fn(TransactionCategory $record) => view(
                'filament.admin.resources.financial.transaction-categories.actions.view-transactions',
                [
                    'transactions' => self::getTransactions($record),
                ],
            ))
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen');
    }

    /**
     * @return Collection<int, Transaction>
     */
    private static function getTransactions(TransactionCategory $category): Collection
    {
        return $category->transactions()
            ->where(Transaction::user_id, auth()->id())
            ->orderByDesc(Transaction::date)
            ->orderByDesc(Transaction::id)
            ->get();
    }
}
