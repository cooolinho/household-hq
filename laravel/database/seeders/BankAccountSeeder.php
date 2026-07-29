<?php

namespace Database\Seeders;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createBankAccount();
        $this->createCSVImportProfile();
    }

    public static function getMainAccount(): ?BankAccount
    {
        return BankAccount::query()
            ->where(BankAccount::name, 'Main Account')
            ->first();
    }

    private function createBankAccount(): void
    {
        $user = UserSeeder::getAdminUser();

        $bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $user->id,
            BankAccount::name => 'Main Account',
            BankAccount::balance => 1000.00,
            BankAccount::account_holder => 'Test Account',
            BankAccount::iban => fake()->iban('DE'),
            BankAccount::bic => fake()->swiftBicNumber(),
            BankAccount::bank_name => 'Test Bank',
            BankAccount::balance_date => now()->subMonths(6)->startOfMonth(),
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
        ]);

        $this->createTransactions($bankAccount, $user);
    }

    /**
     * @param BankAccount $bankAccount
     * @param User $user
     */
    private function createTransactions(BankAccount $bankAccount, User $user): void
    {
        $startBalance = $bankAccount->balance;
        $startDate = $bankAccount->balance_date;

        Transaction::factory()
            ->count(20)
            ->forUser($user->id)
            ->forBankAccount($bankAccount->id)
            ->create()
            ->each(function (Transaction $transaction) use (&$startBalance, &$startDate) {
                $startDate = $startDate->addDays(fake()->numberBetween(1, 5));

                $data = [
                    Transaction::balance => $startBalance += $transaction->amount,
                    Transaction::date => $startDate,
                    Transaction::value_date => $startDate,
                ];

                $data[Transaction::hash] = Transaction::createHash(array_merge(
                    $transaction->toArray(),
                    $data
                ));

                $transaction->update($data);
            });
    }

    private function createCSVImportProfile(): void
    {
        CSVImportProfile::query()
            ->create([
                CSVImportProfile::name => 'Default Import Profile',
                CSVImportProfile::bank => 'ING DIBA',
                CSVImportProfile::delimiter => ';',
                CSVImportProfile::enclosure => '"',
                CSVImportProfile::escape => '\\',
                CSVImportProfile::mapping => [
                    Transaction::date => 0,
                    Transaction::value_date => 1,
                    Transaction::payer => 2,
                    Transaction::description => 3,
                    Transaction::purpose => 4,
                    Transaction::balance => 5,
                    Transaction::balance_currency => 6,
                    Transaction::amount => 7,
                    Transaction::amount_currency => 8,
                ],
                CSVImportProfile::offset_header => 13,
            ]);
    }
}
