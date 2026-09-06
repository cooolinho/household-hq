<?php

use App\Models\Contracts\FinancialGoalTransactionCategory;
use App\Models\Financial\Goal;
use App\Models\Financial\TransactionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FinancialGoalTransactionCategory::PIVOT_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(FinancialGoalTransactionCategory::GOAL_ID)
                ->constrained(Goal::TABLE, Goal::id, 'fgtc_goal_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId(FinancialGoalTransactionCategory::CATEGORY_ID)
                ->constrained(TransactionCategory::TABLE, TransactionCategory::id, 'fgtc_category_id_foreign')
                ->cascadeOnDelete();

            $table->unique([
                FinancialGoalTransactionCategory::GOAL_ID,
                FinancialGoalTransactionCategory::CATEGORY_ID,
            ], 'fgtc_goal_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FinancialGoalTransactionCategory::PIVOT_TABLE);
    }
};
