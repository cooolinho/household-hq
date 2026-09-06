<?php

namespace Database\Seeders\Financial;

use App\Models\Enums\BudgetIconEnum;
use App\Models\Enums\BudgetPeriodEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * BudgetSeeder – Demo-Budgets, die über Transaktionskategorien ausgewertet werden.
 *
 * Hinweis zu "Tabak": Unter den globalen Unterkategorien gibt es keine passende
 * Kategorie, deshalb wird sie hier als benutzereigene Unterkategorie unterhalb der
 * globalen Hauptkategorie "Lebenshaltung" angelegt.
 *
 * Zusätzlich werden Demo-Buchungen im laufenden Zeitraum angelegt, damit die Übersicht
 * alle drei Ampelstufen zeigt: "Lebensmittel & Getränke" gelb, "Tabak" rot, der Rest grün.
 */
class BudgetSeeder extends Seeder
{
    public const string DEMO_TRANSACTION_DESCRIPTION = 'Demo-Budget';

    public static function description(): string
    {
        return 'Legt Demo-Budgets an und verknüpft sie mit Transaktionskategorien';
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
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            $this->command?->warn('BudgetSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $userId = (int)$user->getKey();
        $tobacco = $this->createUserSubcategory($userId, 'Lebenshaltung', 'Tabak');

        $definitions = [
            [
                Budget::name => 'Lebensmittel & Getränke',
                Budget::description => 'Wocheneinkauf, Bäcker und Getränke.',
                Budget::icon => BudgetIconEnum::SHOPPING_CART->name,
                Budget::amount => 600.00,
                Budget::period => BudgetPeriodEnum::MONTHLY->name,
                Budget::include_subcategories => true,
                Budget::sort => 10,
                'categories' => [['Lebenshaltung', 'Lebensmittel & Getränke']],
            ],
            [
                Budget::name => 'Tabak',
                Budget::description => 'Ausgaben für Tabakwaren.',
                Budget::icon => BudgetIconEnum::FIRE->name,
                Budget::amount => 80.00,
                Budget::period => BudgetPeriodEnum::MONTHLY->name,
                Budget::include_subcategories => false,
                Budget::sort => 20,
                'category_ids' => $tobacco !== null ? [(int)$tobacco->getKey()] : [],
            ],
            [
                Budget::name => 'Mobilität',
                Budget::description => 'Tanken, Laden, ÖPNV und Fahrzeugkosten.',
                Budget::icon => BudgetIconEnum::TRUCK->name,
                Budget::amount => 250.00,
                Budget::period => BudgetPeriodEnum::MONTHLY->name,
                Budget::include_subcategories => true,
                Budget::sort => 30,
                'categories' => [['Mobilität', null]],
            ],
            [
                Budget::name => 'Freizeit & Unterhaltung',
                Budget::description => 'Streaming, Hobby, Sport und Ausflüge.',
                Budget::icon => BudgetIconEnum::FILM->name,
                Budget::amount => 200.00,
                Budget::period => BudgetPeriodEnum::MONTHLY->name,
                Budget::include_subcategories => true,
                Budget::warning_threshold => 70,
                Budget::sort => 40,
                'categories' => [['Freizeit & Unterhaltung', null]],
            ],
            [
                Budget::name => 'Urlaub',
                Budget::description => 'Jahresbudget für Reisen.',
                Budget::icon => BudgetIconEnum::GLOBE_ALT->name,
                Budget::amount => 2400.00,
                Budget::period => BudgetPeriodEnum::YEARLY->name,
                Budget::include_subcategories => false,
                Budget::send_mail => true,
                Budget::sort => 50,
                'categories' => [['Freizeit & Unterhaltung', 'Urlaub']],
            ],
        ];

        foreach ($definitions as $definition) {
            $categoryIds = $definition['category_ids']
                ?? $this->resolveCategoryIds($userId, $definition['categories'] ?? []);

            unset($definition['categories'], $definition['category_ids']);

            if ($categoryIds === []) {
                $this->command?->warn(sprintf(
                    'BudgetSeeder: Keine Kategorien für "%s" gefunden – Budget wird übersprungen.',
                    $definition[Budget::name],
                ));

                continue;
            }

            $budget = Budget::query()->firstOrCreate(
                [
                    Budget::user_id => $userId,
                    Budget::name => $definition[Budget::name],
                ],
                $definition,
            );

            $budget->transactionCategories()->syncWithoutDetaching($categoryIds);
        }

        $this->createDemoTransactions($userId);
    }

    private function createUserSubcategory(int $userId, string $parentName, string $name): ?TransactionCategory
    {
        $parent = $this->findCategory($userId, $parentName, null);

        if ($parent === null) {
            $this->command?->warn(sprintf('BudgetSeeder: Hauptkategorie "%s" fehlt.', $parentName));

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
     * @param list<array{0: string, 1: string|null}> $paths
     * @return list<int>
     */
    private function resolveCategoryIds(int $userId, array $paths): array
    {
        $ids = [];

        foreach ($paths as [$parentName, $childName]) {
            $parent = $this->findCategory($userId, $parentName, null);

            if ($parent === null) {
                continue;
            }

            if ($childName === null) {
                $ids[] = (int)$parent->getKey();

                continue;
            }

            $child = $this->findCategory($userId, $childName, (int)$parent->getKey());

            if ($child !== null) {
                $ids[] = (int)$child->getKey();
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Buchungen im laufenden Monat, damit die Übersicht grün, gelb und rot zeigt.
     */
    private function createDemoTransactions(int $userId): void
    {
        $bankAccount = BankAccountSeeder::getMainAccount();

        if (!$bankAccount instanceof BankAccount) {
            $this->command?->warn('BudgetSeeder: Demo-Bankkonto fehlt – Demo-Buchungen werden übersprungen.');

            return;
        }

        $monthStart = CarbonImmutable::now()->startOfMonth();

        // Limit 600 EUR -> 510 EUR (85 %) => Warnung (gelb)
        $groceries = $this->findCategory($userId, 'Lebensmittel & Getränke', $this->parentId($userId, 'Lebenshaltung'));
        // Limit 80 EUR -> 92 EUR (115 %) => überschritten (rot)
        $tobacco = $this->findCategory($userId, 'Tabak', $this->parentId($userId, 'Lebenshaltung'));

        $bookings = [
            [$groceries, 'REWE Markt GmbH', 'Wocheneinkauf', -210.00, 0],
            [$groceries, 'EDEKA', 'Wocheneinkauf', -175.50, 1],
            [$groceries, 'ALDI SUED', 'Wocheneinkauf', -124.50, 2],
            [$tobacco, 'Tabak & Presse', 'Tabakwaren', -56.00, 0],
            [$tobacco, 'Kiosk am Markt', 'Tabakwaren', -36.00, 2],
        ];

        foreach ($bookings as [$category, $payer, $purpose, $amount, $dayOffset]) {
            if (!$category instanceof TransactionCategory) {
                continue;
            }

            $date = $monthStart->addDays($dayOffset);
            $data = [
                Transaction::user_id => $userId,
                Transaction::bank_account_id => (int)$bankAccount->getKey(),
                Transaction::date => $date->toDateString(),
                Transaction::value_date => $date->toDateString(),
                Transaction::payer => $payer,
                Transaction::description => self::DEMO_TRANSACTION_DESCRIPTION,
                Transaction::purpose => $purpose,
                Transaction::amount => $amount,
                Transaction::amount_currency => 'EUR',
            ];
            $data[Transaction::hash] = Transaction::createHash($data);

            $transaction = Transaction::query()->updateOrCreate(
                [
                    Transaction::user_id => $userId,
                    Transaction::bank_account_id => (int)$bankAccount->getKey(),
                    Transaction::payer => $payer,
                    Transaction::description => self::DEMO_TRANSACTION_DESCRIPTION,
                    Transaction::purpose => $purpose,
                ],
                $data,
            );

            $transaction->transactionCategories()->syncWithoutDetaching([$category->getKey()]);
        }
    }

    private function parentId(int $userId, string $name): ?int
    {
        $parent = $this->findCategory($userId, $name, null);

        return $parent !== null ? (int)$parent->getKey() : null;
    }
}
