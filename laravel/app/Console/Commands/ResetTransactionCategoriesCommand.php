<?php

namespace App\Console\Commands;

use App\Models\Contracts\FinancialTransactionCategory;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use Database\Seeders\Financial\TransactionCategorySeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:reset-transaction-categories')]
#[Description('Transaktionskategorien löschen und die Systemkategorien neu seeden')]
class ResetTransactionCategoriesCommand extends Command
{
    private const string OPTION_SYSTEM_ONLY = 'Nur Systemkategorien löschen (Benutzerkategorien behalten)';

    private const string OPTION_ALL = 'Alle Kategorien löschen (inklusive Benutzerkategorien)';

    private const string OPTION_ABORT = 'Abbrechen';

    public function handle(TransactionCategorySeeder $seeder): int
    {
        $selection = (string)$this->choice(
            'Welche Kategorien sollen gelöscht werden?',
            [
                self::OPTION_SYSTEM_ONLY,
                self::OPTION_ALL,
                self::OPTION_ABORT,
            ],
            0,
        );

        if ($selection === self::OPTION_ABORT) {
            $this->line('Abgebrochen.');

            return self::SUCCESS;
        }

        $systemCategoryCount = TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->count();
        $userCategoryCount = TransactionCategory::query()
            ->whereNotNull(TransactionCategory::user_id)
            ->count();
        $deleteUserCategories = $selection === self::OPTION_ALL;
        $deleteCount = $deleteUserCategories
            ? $systemCategoryCount + $userCategoryCount
            : $systemCategoryCount;

        $this->line(sprintf('Systemkategorien: %d', $systemCategoryCount));
        $this->line(sprintf('Benutzerkategorien: %d', $userCategoryCount));
        $this->warn(sprintf('%d Kategorie(n) werden gelöscht.', $deleteCount));

        if (!$this->confirm(
            'Möchtest du die ausgewählten Kategorien wirklich löschen und den System-Seeder ausführen?',
            false,
        )) {
            $this->line('Abgebrochen.');

            return self::SUCCESS;
        }

        try {
            $deletedCount = DB::transaction(function () use ($deleteUserCategories, $seeder): int {
                $categoryIds = TransactionCategory::query()
                    ->when(
                        !$deleteUserCategories,
                        fn(Builder $query): Builder => $query->whereNull(TransactionCategory::user_id),
                    )
                    ->pluck(TransactionCategory::id)
                    ->map(static fn(int|string $id): int => (int)$id)
                    ->all();

                if ($categoryIds !== []) {
                    $ruleIds = TransactionCategoryRule::query()
                        ->whereIn(TransactionCategoryRule::transaction_category_id, $categoryIds)
                        ->pluck(TransactionCategoryRule::id)
                        ->map(static fn(int|string $id): int => (int)$id)
                        ->all();

                    if ($ruleIds !== []) {
                        TransactionCategoryRuleUserSetting::query()
                            ->whereIn(TransactionCategoryRuleUserSetting::transaction_category_rule_id, $ruleIds)
                            ->delete();
                        TransactionCategoryCriterion::query()
                            ->whereIn(TransactionCategoryCriterion::transaction_category_rule_id, $ruleIds)
                            ->delete();
                    }

                    DB::table(FinancialTransactionCategory::PIVOT_TABLE)
                        ->whereIn(FinancialTransactionCategory::CATEGORY_ID, $categoryIds)
                        ->delete();
                    TransactionCategoryRule::query()
                        ->whereIn(TransactionCategoryRule::id, $ruleIds)
                        ->delete();
                    TransactionCategory::query()
                        ->whereIn(TransactionCategory::parent_id, $categoryIds)
                        ->update([TransactionCategory::parent_id => null]);

                    $deletedCount = TransactionCategory::query()
                        ->whereIn(TransactionCategory::id, $categoryIds)
                        ->delete();
                } else {
                    $deletedCount = 0;
                }

                $seeder
                    ->setContainer(app())
                    ->setCommand($this)
                    ->__invoke();

                return $deletedCount;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error(sprintf(
                'Die Transaktionskategorien konnten nicht zurückgesetzt werden: %s',
                $exception->getMessage(),
            ));

            return self::FAILURE;
        }

        $this->info(sprintf('%d Kategorie(n) wurden gelöscht.', $deletedCount));
        $this->info('Die Systemkategorien wurden erfolgreich neu geseedet.');

        return self::SUCCESS;
    }
}
