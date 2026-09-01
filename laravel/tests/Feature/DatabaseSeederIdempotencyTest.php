<?php

namespace Tests\Feature;

use App\Models\ContactPerson;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use App\Models\Financial\Insurance;
use App\Models\Financial\InsuranceCategory;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_create_duplicate_demo_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstRunCounts = $this->demoCounts();

        self::assertSame([
            'users' => 2,
            'contacts' => 3,
            'tags' => 10,
            'bank_accounts' => 1,
            'csv_import_profiles' => 1,
            'transactions' => 31,
            'insurance_categories' => 21,
            'insurances' => 2,
            'fixed_cost_categories' => 50,
            'fixed_costs' => 8,
            'transaction_categories' => 14,
            'transaction_category_rules' => 11,
            'transaction_category_criteria' => 30,
            'measurement_devices' => 2,
            'reading_entries' => 12,
            'contracts' => 3,
            'contract_prices' => 7,
        ], $firstRunCounts);
        self::assertSame(14, TransactionCategory::query()->whereNull(TransactionCategory::user_id)->count());
        self::assertSame(11, TransactionCategoryRule::query()->whereNull(TransactionCategoryRule::user_id)->count());

        $this->seed(DatabaseSeeder::class);

        self::assertSame($firstRunCounts, $this->demoCounts());
        self::assertSame(
            Transaction::query()->count(),
            Transaction::query()->distinct(Transaction::hash)->count(Transaction::hash),
        );
    }

    /**
     * @return array<string, int>
     */
    private function demoCounts(): array
    {
        return [
            'users' => User::query()->count(),
            'contacts' => ContactPerson::query()->count(),
            'tags' => Tag::query()->count(),
            'bank_accounts' => BankAccount::query()->count(),
            'csv_import_profiles' => CSVImportProfile::query()->count(),
            'transactions' => Transaction::query()->count(),
            'insurance_categories' => InsuranceCategory::query()->count(),
            'insurances' => Insurance::query()->count(),
            'fixed_cost_categories' => FixedCostCategory::query()->count(),
            'fixed_costs' => FixedCost::query()->count(),
            'transaction_categories' => TransactionCategory::query()->count(),
            'transaction_category_rules' => TransactionCategoryRule::query()->count(),
            'transaction_category_criteria' => TransactionCategoryCriterion::query()->count(),
            'measurement_devices' => MeasurementDevice::query()->count(),
            'reading_entries' => ReadingEntry::query()->count(),
            'contracts' => MeasurementDeviceContract::query()->count(),
            'contract_prices' => MeasurementDeviceContractPrice::query()->count(),
        ];
    }
}
