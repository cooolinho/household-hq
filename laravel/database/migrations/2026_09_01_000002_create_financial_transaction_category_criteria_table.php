<?php

use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(TransactionCategoryCriterion::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(TransactionCategoryCriterion::transaction_category_rule_id);
            $table->foreign(TransactionCategoryCriterion::transaction_category_rule_id, 'ftcc_rule_id_foreign')
                ->references('id')
                ->on(TransactionCategoryRule::TABLE)
                ->cascadeOnDelete();
            $table->string(TransactionCategoryCriterion::field);
            $table->string(TransactionCategoryCriterion::operator);
            $table->string(TransactionCategoryCriterion::value);
            $table->string(TransactionCategoryCriterion::value_secondary)->nullable();
            $table->boolean(TransactionCategoryCriterion::case_sensitive)->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TransactionCategoryCriterion::TABLE);
    }
};
