<?php

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(FixedCost::TABLE, FixedCost::category_id)) {
                $table->foreignIdFor(FixedCostCategory::class, FixedCost::category_id)
                    ->nullable()
                    ->after(FixedCost::amount)
                    ->constrained(FixedCostCategory::TABLE)
                    ->nullOnDelete();
            }
        });

        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(FixedCost::TABLE, FixedCost::category)) {
                $table->dropColumn(FixedCost::category);
            }
        });
    }

    public function down(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(FixedCost::TABLE, FixedCost::category)) {
                $table->string(FixedCost::category)->nullable()->after(FixedCost::amount);
            }
        });

        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(FixedCost::TABLE, FixedCost::category_id)) {
                $table->dropConstrainedForeignId(FixedCost::category_id);
            }
        });
    }
};

