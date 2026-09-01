<?php

namespace Database\Seeders\Financial;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Models\User;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BankAccountSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt ein Demo-Bankkonto, Importprofil und Transaktionen an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [UserSeeder::class];
    }

    public static function getMainAccount(): ?BankAccount
    {
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            return null;
        }

        return BankAccount::query()
            ->where(BankAccount::user_id, $user->getKey())
            ->where(BankAccount::name, 'Main Account')
            ->first();
    }

    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            $this->command?->warn('BankAccountSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $bankAccount = $this->createBankAccount($user);
        $this->createTransactions($bankAccount, $user);
        $this->createCSVImportProfile();
    }

    private function createBankAccount(User $user): BankAccount
    {
        return BankAccount::query()->firstOrCreate(
            [
                BankAccount::user_id => $user->getKey(),
                BankAccount::name => 'Main Account',
            ],
            [
                BankAccount::balance => 1000.00,
                BankAccount::account_holder => 'Test Account',
                BankAccount::iban => fake()->iban('DE'),
                BankAccount::bic => fake()->swiftBicNumber(),
                BankAccount::bank_name => 'Test Bank',
                BankAccount::balance_date => now()->subMonths(6)->startOfMonth(),
                BankAccount::type => BankAccountTypeEnum::GIRO->name,
            ],
        );
    }

    private function createTransactions(BankAccount $bankAccount, User $user): void
    {
        $balance = 1000.00;
        $date = Carbon::parse('2026-01-01');

        for ($index = 1; $index <= 20; $index++) {
            $date = $date->copy()->addDays($index);
            $amount = round(-45.00 + ($index * 4.25), 2);
            $data = [
                Transaction::date => $date->toDateString(),
                Transaction::value_date => $date->toDateString(),
                Transaction::payer => sprintf('Demo Händler %02d', $index),
                Transaction::description => 'Demo-Kontobewegung',
                Transaction::purpose => sprintf('Demo-Buchung %02d', $index),
                Transaction::balance => $balance += $amount,
                Transaction::balance_currency => 'EUR',
                Transaction::amount => $amount,
                Transaction::amount_currency => 'EUR',
                Transaction::user_id => $user->getKey(),
                Transaction::bank_account_id => $bankAccount->getKey(),
            ];
            $data[Transaction::hash] = Transaction::createHash($data);

            Transaction::query()->firstOrCreate(
                [
                    Transaction::hash => $data[Transaction::hash],
                ],
                $data,
            );
        }
    }

    private function createCSVImportProfile(): void
    {
        CSVImportProfile::query()
            ->firstOrCreate(
                [
                    CSVImportProfile::name => 'Default Import Profile',
                ],
                [
                    CSVImportProfile::bank => 'ING DIBA',
                    CSVImportProfile::delimiter => ';',
                    CSVImportProfile::enclosure => '"',
                    CSVImportProfile::escape => '\\',
                    CSVImportProfile::amount_format => 'de_de',
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
                ],
            );
    }
}
