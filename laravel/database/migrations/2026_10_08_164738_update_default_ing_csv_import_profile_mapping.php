<?php
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateLegacyIngMappings($this->legacyMapping(), $this->currentMapping());
        $this->updateEmptyDefaultProfile($this->currentMapping(), 13);
    }

    public function down(): void
    {
        $this->updateLegacyIngMappings($this->currentMapping(), $this->legacyMapping());
        $this->restoreEmptyDefaultProfile();
    }

    private function updateLegacyIngMappings(array $from, array $to): void
    {
        $profiles = DB::table(CSVImportProfile::TABLE)
            ->where(CSVImportProfile::bank, 'ING DIBA')
            ->where(CSVImportProfile::offset_header, 13)
            ->get([CSVImportProfile::id, CSVImportProfile::mapping]);

        foreach ($profiles as $profile) {
            $mapping = json_decode($profile->{CSVImportProfile::mapping}, true);

            if (!$this->matchesMapping($mapping, $from)) {
                continue;
            }

            DB::table(CSVImportProfile::TABLE)
                ->where(CSVImportProfile::id, $profile->{CSVImportProfile::id})
                ->update([
                    CSVImportProfile::mapping => json_encode($to, JSON_THROW_ON_ERROR),
                    CSVImportProfile::updated_at => now(),
                ]);
        }
    }

    private function matchesMapping(?array $mapping, array $expected): bool
    {
        if ($mapping === null || count($mapping) !== count($expected)) {
            return false;
        }

        foreach ($expected as $field => $index) {
            if (!isset($mapping[$field]) || (int) $mapping[$field] !== $index) {
                return false;
            }
        }

        return true;
    }

    private function legacyMapping(): array
    {
        return [
            Transaction::date => 0,
            Transaction::value_date => 1,
            Transaction::payer => 2,
            Transaction::description => 3,
            Transaction::purpose => 4,
            Transaction::balance => 5,
            Transaction::balance_currency => 6,
            Transaction::amount => 7,
            Transaction::amount_currency => 8,
        ];
    }

    private function currentMapping(): array
    {
        return [
            Transaction::date => 0,
            Transaction::value_date => 1,
            Transaction::payer => 2,
            Transaction::description => 3,
            Transaction::purpose => 4,
            Transaction::balance => 6,
            Transaction::balance_currency => 7,
            Transaction::amount => 8,
            Transaction::amount_currency => 9,
        ];
    }

    private function updateEmptyDefaultProfile(array $mapping, int $offsetHeader): void
    {
        DB::table(CSVImportProfile::TABLE)
            ->where(CSVImportProfile::name, 'Default Import Profile')
            ->where(CSVImportProfile::bank, 'ING DIBA')
            ->where(CSVImportProfile::offset_header, 0)
            ->whereNull(CSVImportProfile::mapping)
            ->update([
                CSVImportProfile::mapping => json_encode($mapping, JSON_THROW_ON_ERROR),
                CSVImportProfile::offset_header => $offsetHeader,
                CSVImportProfile::updated_at => now(),
            ]);
    }

    private function restoreEmptyDefaultProfile(): void
    {
        DB::table(CSVImportProfile::TABLE)
            ->where(CSVImportProfile::name, 'Default Import Profile')
            ->where(CSVImportProfile::bank, 'ING DIBA')
            ->where(CSVImportProfile::offset_header, 13)
            ->where(CSVImportProfile::mapping, json_encode($this->currentMapping(), JSON_THROW_ON_ERROR))
            ->update([
                CSVImportProfile::mapping => null,
                CSVImportProfile::offset_header => 0,
                CSVImportProfile::updated_at => now(),
            ]);
    }
};
