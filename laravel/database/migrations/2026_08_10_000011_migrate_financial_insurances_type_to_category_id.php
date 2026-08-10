<?php

use App\Models\Financial\Insurance;
use App\Models\Financial\InsuranceCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(Insurance::TABLE, Insurance::category_id)) {
                $table->foreignIdFor(InsuranceCategory::class, Insurance::category_id)
                    ->nullable()
                    ->after(Insurance::number)
                    ->constrained(InsuranceCategory::TABLE)
                    ->nullOnDelete();
            }
        });

        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(Insurance::TABLE, 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(Insurance::TABLE, 'type')) {
                $table->string('type')->nullable()->after(Insurance::number);
            }
        });

        Schema::table(Insurance::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(Insurance::TABLE, Insurance::category_id)) {
                $table->dropConstrainedForeignId(Insurance::category_id);
            }
        });
    }
};

