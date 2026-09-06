<?php

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->string(TransactionCategoryRule::key)
                ->nullable()
                ->unique('ftcr_key_unique')
                ->after(TransactionCategoryRule::user_id);
        });

        Schema::table(TransactionCategoryCriterion::TABLE, function (Blueprint $table) {
            $table->text(TransactionCategoryCriterion::value)->change();
        });

        $this->deleteLegacyDefaultRules();
    }

    /**
     * Entfernt die alten Seeder-Default-Regeln ("Kategoriename im Verwendungszweck enthalten").
     * Erkennungsmerkmal: globale Regel ohne Key mit genau einem Kriterium
     * purpose/contains/<kategoriename in lowercase>.
     */
    private function deleteLegacyDefaultRules(): void
    {
        $ruleIds = DB::table(TransactionCategoryRule::TABLE . ' as rules')
            ->join(
                TransactionCategory::TABLE . ' as categories',
                'categories.' . TransactionCategory::id,
                '=',
                'rules.' . TransactionCategoryRule::transaction_category_id,
            )
            ->join(
                TransactionCategoryCriterion::TABLE . ' as criteria',
                'criteria.' . TransactionCategoryCriterion::transaction_category_rule_id,
                '=',
                'rules.' . TransactionCategoryRule::id,
            )
            ->whereNull('rules.' . TransactionCategoryRule::user_id)
            ->whereNull('rules.' . TransactionCategoryRule::key)
            ->where('criteria.' . TransactionCategoryCriterion::field, TransactionCategoryCriterion::FIELD_PURPOSE)
            ->where('criteria.' . TransactionCategoryCriterion::operator, TransactionCategoryCriterion::OP_CONTAINS)
            ->whereRaw('criteria.`value` = LOWER(categories.`name`)')
            ->groupBy('rules.' . TransactionCategoryRule::id)
            ->havingRaw('COUNT(criteria.`id`) = 1')
            ->pluck('rules.' . TransactionCategoryRule::id)
            ->map(static fn(int|string $id): int => (int)$id)
            ->all();

        if ($ruleIds === []) {
            return;
        }

        DB::table(TransactionCategoryRuleUserSetting::TABLE)
            ->whereIn(TransactionCategoryRuleUserSetting::transaction_category_rule_id, $ruleIds)
            ->delete();
        DB::table(TransactionCategoryCriterion::TABLE)
            ->whereIn(TransactionCategoryCriterion::transaction_category_rule_id, $ruleIds)
            ->delete();
        DB::table(TransactionCategoryRule::TABLE)
            ->whereIn(TransactionCategoryRule::id, $ruleIds)
            ->delete();
    }

    public function down(): void
    {
        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->dropUnique('ftcr_key_unique');
            $table->dropColumn(TransactionCategoryRule::key);
        });

        Schema::table(TransactionCategoryCriterion::TABLE, function (Blueprint $table) {
            $table->string(TransactionCategoryCriterion::value)->change();
        });
    }
};
