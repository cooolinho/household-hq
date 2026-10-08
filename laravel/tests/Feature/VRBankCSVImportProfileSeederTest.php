<?php

namespace Tests\Feature;

use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use Database\Seeders\Financial\VRBankCSVImportProfileSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VRBankCSVImportProfileSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create(CSVImportProfile::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string(CSVImportProfile::name);
            $table->string(CSVImportProfile::bank)->nullable();
            $table->string(CSVImportProfile::delimiter, 1)->default(';');
            $table->string(CSVImportProfile::enclosure, 1)->default('"');
            $table->string(CSVImportProfile::escape, 1)->default('');
            $table->string(CSVImportProfile::amount_format)->default('de_de');
            $table->tinyInteger(CSVImportProfile::offset_header)->default(0);
            $table->json(CSVImportProfile::mapping)->nullable();
            $table->timestamps();
        });

        Schema::create(BankAccount::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string(BankAccount::name);
            $table->string(BankAccount::bank_name)->nullable();
            $table->unsignedBigInteger(BankAccount::csv_profile_id);
            $table->timestamps();
        });
    }

    public function test_it_creates_the_vr_bank_profile_and_assigns_it_to_the_existing_account_idempotently(): void
    {
        $defaultProfile = CSVImportProfile::query()->create([
            CSVImportProfile::name => 'Default Import Profile',
            CSVImportProfile::bank => 'ING DIBA',
            CSVImportProfile::delimiter => ';',
            CSVImportProfile::enclosure => '"',
            CSVImportProfile::escape => '\\',
            CSVImportProfile::amount_format => 'de_de',
            CSVImportProfile::offset_header => 0,
            CSVImportProfile::mapping => [],
        ]);
        $account = BankAccount::query()->create([
            BankAccount::name => 'VR BANK',
            BankAccount::csv_profile_id => $defaultProfile->getKey(),
        ]);

        $this->seed(VRBankCSVImportProfileSeeder::class);
        $this->seed(VRBankCSVImportProfileSeeder::class);

        $profile = CSVImportProfile::query()
            ->where(CSVImportProfile::name, 'VR BANK')
            ->sole();

        self::assertSame('VR BANK', $profile->bank);
        self::assertSame(';', $profile->delimiter);
        self::assertSame(0, $profile->offset_header);
        self::assertSame([
            Transaction::date => 4,
            Transaction::value_date => 5,
            Transaction::payer => 6,
            Transaction::description => 9,
            Transaction::purpose => 10,
            Transaction::balance => 13,
            Transaction::balance_currency => 12,
            Transaction::amount => 11,
            Transaction::amount_currency => 12,
        ], $profile->mapping);
        self::assertSame($profile->getKey(), $account->fresh()->csv_profile_id);
        self::assertSame(1, CSVImportProfile::query()->where(CSVImportProfile::name, 'VR BANK')->count());
    }
}

