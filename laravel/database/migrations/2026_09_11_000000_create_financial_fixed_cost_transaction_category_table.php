<?php

use App\Models\Contracts\FinancialFixedCostTransactionCategory;
use App\Models\Financial\FixedCost;
use App\Models\Financial\TransactionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FinancialFixedCostTransactionCategory::PIVOT_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(FinancialFixedCostTransactionCategory::FIXED_COST_ID)
                ->constrained(FixedCost::TABLE, FixedCost::id, 'ffctc_fixed_cost_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId(FinancialFixedCostTransactionCategory::CATEGORY_ID)
                ->constrained(TransactionCategory::TABLE, TransactionCategory::id, 'ffctc_category_id_foreign')
                ->cascadeOnDelete();

            $table->unique([
                FinancialFixedCostTransactionCategory::FIXED_COST_ID,
                FinancialFixedCostTransactionCategory::CATEGORY_ID,
            ], 'ffctc_fixed_cost_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FinancialFixedCostTransactionCategory::PIVOT_TABLE);
    }
};
