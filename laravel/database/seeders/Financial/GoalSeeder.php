<?php

namespace Database\Seeders\Financial;

use App\Models\Enums\GoalDirectionEnum;
use App\Models\Enums\GoalIconEnum;
use App\Models\Enums\GoalTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Goal;
use App\Models\Financial\GoalContribution;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * GoalSeeder – Demo-Ziele: eine Abzahlung (rein manuell geführt) und ein Sparziel
 * (automatisch über eine Transaktionskategorie ermittelt).
 *
 * Hinweis zu "Sparen": Unter den globalen Unterkategorien gibt es keine passende
 * Kategorie, deshalb wird sie hier als benutzereigene Unterkategorie unterhalb der
 * globalen Hauptkategorie "Finanzen & Versicherungen" angelegt – analog zu "Tabak"
 * im BudgetSeeder.
 */
class GoalSeeder extends Seeder
{
    public const string DEMO_TRANSACTION_DESCRIPTION = 'Demo-Ziel';

    public static function description(): string
    {
        return 'Legt Demo-Ziele an, teils manuell, teils über Transaktionskategorien ausgewertet';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [
            UserSeeder::class,
            BankAccountSeeder::class,
            TransactionCategorySeeder::class,
            TransactionSeeder::class,
        ];
    }

    public function run(): void
    {
        $user = UserSeeder::getAppUser();

        if (!$user instanceof User) {
            $this->command?->warn('GoalSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $userId = (int)$user->getKey();

        $this->createDebtPayoffGoal($userId);
        $this->createSavingsGoal($userId);
    }

    /**
     * Abzahlung eines Beamers – rein über manuelle Einzahlungen geführt, keine Kategorie.
     */
    private function createDebtPayoffGoal(int $userId): void
    {
        $startDate = CarbonImmutable::now()->subMonths(3)->startOfMonth();

        $goal = Goal::query()->firstOrCreate(
            [
                Goal::user_id => $userId,
                Goal::name => 'Beamer Abzahlung',
            ],
            [
                Goal::description => 'Ratenzahlung für den neuen Heimkino-Beamer.',
                Goal::icon => GoalIconEnum::SCALE->name,
                Goal::type => GoalTypeEnum::DEBT_PAYOFF->name,
                Goal::direction => GoalDirectionEnum::EXPENSE->name,
                Goal::start_amount => 1200.00,
                Goal::target_amount => 0.00,
                Goal::start_date => $startDate->toDateString(),
                Goal::sort => 10,
            ],
        );

        foreach ([0, 1, 2] as $monthOffset) {
            $date = $startDate->addMonths($monthOffset)->addDays(14);

            GoalContribution::query()->updateOrCreate(
                [
                    GoalContribution::goal_id => $goal->getKey(),
                    GoalContribution::date => $date->toDateString(),
                    GoalContribution::amount => 150.00,
                ],
                [
                    GoalContribution::note => 'Rate ' . ($monthOffset + 1),
                ],
            );
        }
    }

    /**
     * Sparziel für den nächsten Urlaub – automatisch über die Kategorie "Sparen" ermittelt.
     */
    private function createSavingsGoal(int $userId): void
    {
        $category = $this->createUserSubcategory($userId, 'Finanzen & Versicherungen', 'Sparen');
        $startDate = CarbonImmutable::now()->subMonths(3)->startOfMonth();
        $targetDate = $startDate->addMonths(9);

        $goal = Goal::query()->firstOrCreate(
            [
                Goal::user_id => $userId,
                Goal::name => 'Urlaub sparen',
            ],
            [
                Goal::description => 'Rücklage für den nächsten Sommerurlaub.',
                Goal::icon => GoalIconEnum::GLOBE_ALT->name,
                Goal::type => GoalTypeEnum::SAVINGS->name,
                Goal::direction => GoalDirectionEnum::EXPENSE->name,
                Goal::start_amount => 200.00,
                Goal::target_amount => 3000.00,
                Goal::start_date => $startDate->toDateString(),
                Goal::target_date => $targetDate->toDateString(),
                Goal::sort => 20,
            ],
        );

        if ($category !== null) {
            $goal->transactionCategories()->syncWithoutDetaching([$category->getKey()]);
        }

        $this->createDemoTransactions($userId, $category, $startDate);
    }

    private function createUserSubcategory(int $userId, string $parentName, string $name): ?TransactionCategory
    {
        $parent = $this->findCategory($userId, $parentName, null);

        if ($parent === null) {
            $this->command?->warn(sprintf('GoalSeeder: Hauptkategorie "%s" fehlt.', $parentName));

            return null;
        }

        return TransactionCategory::query()->firstOrCreate(
            [
                TransactionCategory::user_id => $userId,
                TransactionCategory::name => $name,
                TransactionCategory::parent_id => (int)$parent->getKey(),
            ],
            [
                TransactionCategory::active => true,
            ],
        );
    }

    private function findCategory(int $userId, string $name, ?int $parentId): ?TransactionCategory
    {
        return TransactionCategory::query()
            ->visibleForUser($userId)
            ->where(TransactionCategory::name, $name)
            ->where(TransactionCategory::parent_id, $parentId)
            ->first();
    }

    /**
     * Monatliche Überweisungen auf das Sparkonto, damit der Fortschritt automatisch
     * aus Transaktionen ermittelt werden kann.
     */
    private function createDemoTransactions(int $userId, ?TransactionCategory $category, CarbonImmutable $startDate): void
    {
        $bankAccount = BankAccountSeeder::getMainAccount();

        if (!$bankAccount instanceof BankAccount || $category === null) {
            $this->command?->warn('GoalSeeder: Demo-Bankkonto oder Kategorie fehlt – Demo-Buchungen werden übersprungen.');

            return;
        }

        foreach ([0, 1, 2] as $monthOffset) {
            $date = $startDate->addMonths($monthOffset)->addDays(4);
            $purpose = 'Sparplan Urlaub ' . ($monthOffset + 1);

            $data = [
                Transaction::user_id => $userId,
                Transaction::bank_account_id => (int)$bankAccount->getKey(),
                Transaction::date => $date->toDateString(),
                Transaction::value_date => $date->toDateString(),
                Transaction::payer => 'Eigene Überweisung',
                Transaction::description => self::DEMO_TRANSACTION_DESCRIPTION,
                Transaction::purpose => $purpose,
                Transaction::amount => -300.00,
                Transaction::amount_currency => 'EUR',
            ];
            $data[Transaction::hash] = Transaction::createHash($data);

            $transaction = Transaction::query()->updateOrCreate(
                [
                    Transaction::user_id => $userId,
                    Transaction::bank_account_id => (int)$bankAccount->getKey(),
                    Transaction::payer => 'Eigene Überweisung',
                    Transaction::description => self::DEMO_TRANSACTION_DESCRIPTION,
                    Transaction::purpose => $purpose,
                ],
                $data,
            );

            $transaction->transactionCategories()->syncWithoutDetaching([$category->getKey()]);
        }
    }
}
