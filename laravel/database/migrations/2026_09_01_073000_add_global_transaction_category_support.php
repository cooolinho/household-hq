<?php

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY %s BIGINT UNSIGNED NULL',
            TransactionCategory::TABLE,
            TransactionCategory::user_id,
        ));

        Schema::table(TransactionCategory::TABLE, function (Blueprint $table) {
            $table->index(TransactionCategory::user_id, 'ftc_user_id_idx');
            $table->dropUnique('ftc_user_name_parent_unique');
        });

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX ftc_owner_name_parent_unique ON %s ((COALESCE(%s, 0)), %s, (COALESCE(%s, 0)))',
            TransactionCategory::TABLE,
            TransactionCategory::user_id,
            TransactionCategory::name,
            TransactionCategory::parent_id,
        ));

        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->foreignIdFor(User::class, TransactionCategoryRule::user_id)
                ->nullable()
                ->after(TransactionCategoryRule::transaction_category_id)
                ->constrained(User::TABLE)
                ->nullOnDelete();
            $table->index([
                TransactionCategoryRule::transaction_category_id,
                TransactionCategoryRule::user_id,
            ], 'ftcr_category_user_idx');
        });

        Schema::create(TransactionCategoryRuleUserSetting::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(TransactionCategoryRuleUserSetting::user_id);
            $table->foreign(TransactionCategoryRuleUserSetting::user_id, 'ftcrus_user_id_foreign')
                ->references('id')
                ->on(User::TABLE)
                ->cascadeOnDelete();
            $table->unsignedBigInteger(TransactionCategoryRuleUserSetting::transaction_category_rule_id);
            $table->foreign(
                TransactionCategoryRuleUserSetting::transaction_category_rule_id,
                'ftcrus_rule_id_foreign'
            )
                ->references(TransactionCategoryRule::id)
                ->on(TransactionCategoryRule::TABLE)
                ->cascadeOnDelete();
            $table->boolean(TransactionCategoryRuleUserSetting::active)->default(true);
            $table->timestamps();

            $table->unique(
                [
                    TransactionCategoryRuleUserSetting::user_id,
                    TransactionCategoryRuleUserSetting::transaction_category_rule_id,
                ],
                'ftcrus_user_rule_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TransactionCategoryRuleUserSetting::TABLE);

        Schema::table(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->dropIndex('ftcr_category_user_idx');
            $table->dropConstrainedForeignId(TransactionCategoryRule::user_id);
        });

        Schema::table(TransactionCategory::TABLE, function (Blueprint $table) {
            $table->dropUnique('ftc_owner_name_parent_unique');
        });

        Schema::table(TransactionCategory::TABLE, function (Blueprint $table) {
            $table->unique([
                TransactionCategory::user_id,
                TransactionCategory::name,
                TransactionCategory::parent_id,
            ], 'ftc_user_name_parent_unique');
            $table->dropIndex('ftc_user_id_idx');
        });

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY %s BIGINT UNSIGNED NOT NULL',
            TransactionCategory::TABLE,
            TransactionCategory::user_id,
        ));
    }
};
