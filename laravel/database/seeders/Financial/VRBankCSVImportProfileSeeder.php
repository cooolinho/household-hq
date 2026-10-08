<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use Illuminate\Database\Seeder;

class VRBankCSVImportProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profile = CSVImportProfile::query()->updateOrCreate(
            [CSVImportProfile::name => 'VR BANK'],
            [
                CSVImportProfile::bank => 'VR BANK',
                CSVImportProfile::delimiter => ';',
                CSVImportProfile::enclosure => '"',
                CSVImportProfile::escape => '\\',
                CSVImportProfile::amount_format => 'de_de',
                CSVImportProfile::offset_header => 0,
                CSVImportProfile::mapping => [
                    Transaction::date => 4,
                    Transaction::value_date => 5,
                    Transaction::payer => 6,
                    Transaction::description => 9,
                    Transaction::purpose => 10,
                    Transaction::balance => 13,
                    Transaction::balance_currency => 12,
                    Transaction::amount => 11,
                    Transaction::amount_currency => 12,
                ],
            ],
        );

        BankAccount::query()
            ->where(function ($query): void {
                $query->where(BankAccount::name, 'VR BANK')
                    ->orWhere(BankAccount::bank_name, 'VR BANK');
            })
            ->update([BankAccount::csv_profile_id => $profile->getKey()]);
    }
}
