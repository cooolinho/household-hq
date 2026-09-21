<?php

use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(BankAccount::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger(BankAccount::csv_profile_id)->nullable()->after(BankAccount::type);
        });

        $profileId = CSVImportProfile::orderByDesc(CSVImportProfile::id)
            ->value(CSVImportProfile::id);

        if (!$profileId) {
            $profileId = (int) DB::table(CSVImportProfile::TABLE)->insertGetId([
                CSVImportProfile::name => 'Default Import Profile',
                CSVImportProfile::bank => 'ING DIBA',
                CSVImportProfile::delimiter => ';',
                CSVImportProfile::enclosure => '"',
                CSVImportProfile::escape => '\\',
                CSVImportProfile::amount_format => 'de_de',
                CSVImportProfile::offset_header => 0,
                CSVImportProfile::created_at => now(),
                CSVImportProfile::updated_at => now(),
            ]);
        }

        DB::table(BankAccount::TABLE)
            ->whereNull(BankAccount::csv_profile_id)
            ->update([BankAccount::csv_profile_id => $profileId]);

        Schema::table(BankAccount::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger(BankAccount::csv_profile_id)->nullable(false)->change();
        });

        Schema::table(BankAccount::TABLE, function (Blueprint $table) {
            $table->foreign(BankAccount::csv_profile_id)
                ->references(CSVImportProfile::id)
                ->on(CSVImportProfile::TABLE)
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(BankAccount::TABLE, function (Blueprint $table) {
            $table->dropForeign([BankAccount::csv_profile_id]);
            $table->dropColumn(BankAccount::csv_profile_id);
        });
    }
};
