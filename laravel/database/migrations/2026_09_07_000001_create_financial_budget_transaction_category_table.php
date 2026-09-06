<?php

use App\Models\Contracts\FinancialBudgetTransactionCategory;
use App\Models\Financial\Budget;
use App\Models\Financial\TransactionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FinancialBudgetTransactionCategory::PIVOT_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(FinancialBudgetTransactionCategory::BUDGET_ID)
                ->constrained(Budget::TABLE, Budget::id, 'fbtc_budget_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId(FinancialBudgetTransactionCategory::CATEGORY_ID)
                ->constrained(TransactionCategory::TABLE, TransactionCategory::id, 'fbtc_category_id_foreign')
                ->cascadeOnDelete();

            $table->unique([
                FinancialBudgetTransactionCategory::BUDGET_ID,
                FinancialBudgetTransactionCategory::CATEGORY_ID,
            ], 'fbtc_budget_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FinancialBudgetTransactionCategory::PIVOT_TABLE);
    }
};
