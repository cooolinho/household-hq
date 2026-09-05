<?php

namespace Tests\Feature;

use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategoryTransactionCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_distinct_user_transactions_in_category_descendants(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bankAccount = BankAccount::query()->create([
            BankAccount::name => 'Main Account',
            BankAccount::user_id => $user->getKey(),
        ]);
        $otherBankAccount = BankAccount::query()->create([
            BankAccount::name => 'Other Account',
            BankAccount::user_id => $otherUser->getKey(),
        ]);

        $root = $this->createCategory($user, 'Root');
        $child = $this->createCategory($user, 'Child', $root);
        $grandchild = $this->createCategory($user, 'Grandchild', $child);

        $rootTransaction = Transaction::factory()
            ->forUser($user->getKey())
            ->forBankAccount($bankAccount->getKey())
            ->create();
        $rootTransaction->transactionCategories()->attach($root);

        $childTransaction = Transaction::factory()
            ->forUser($user->getKey())
            ->forBankAccount($bankAccount->getKey())
            ->create();
        $childTransaction->transactionCategories()->attach($child);

        $multiCategoryTransaction = Transaction::factory()
            ->forUser($user->getKey())
            ->forBankAccount($bankAccount->getKey())
            ->create();
        $multiCategoryTransaction->transactionCategories()->attach([$child->getKey(), $grandchild->getKey()]);

        $otherUserTransaction = Transaction::factory()
            ->forUser($otherUser->getKey())
            ->forBankAccount($otherBankAccount->getKey())
            ->create();
        $otherUserTransaction->transactionCategories()->attach($child);

        self::assertSame(3, $root->getTransactionCountIncludingDescendants($user->getKey()));
        self::assertSame(2, $child->getTransactionCountIncludingDescendants($user->getKey()));
        self::assertSame(1, $grandchild->getTransactionCountIncludingDescendants($user->getKey()));
    }

    private function createCategory(
        User                 $user,
        string               $name,
        ?TransactionCategory $parent = null,
    ): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => $user->getKey(),
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => $parent?->getKey(),
            TransactionCategory::active => true,
        ]);
    }
}
