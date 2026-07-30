<?php

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostMatchingRule;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FixedCostMatchingRule::TABLE, function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, FixedCostMatchingRule::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();

            $table->foreignIdFor(FixedCost::class, FixedCostMatchingRule::fixed_cost_id)
                ->constrained(FixedCost::TABLE)
                ->cascadeOnDelete();

            $table->string(FixedCostMatchingRule::fingerprint, 64);
            $table->string(FixedCostMatchingRule::payer_token)->nullable();
            $table->string(FixedCostMatchingRule::purpose_token)->nullable();
            $table->tinyInteger(FixedCostMatchingRule::amount_sign);
            $table->decimal(FixedCostMatchingRule::amount_min, 15, 2)->nullable();
            $table->decimal(FixedCostMatchingRule::amount_max, 15, 2)->nullable();
            $table->decimal(FixedCostMatchingRule::positive_weight, 7, 2)->default(0);
            $table->decimal(FixedCostMatchingRule::negative_weight, 7, 2)->default(0);
            $table->string(FixedCostMatchingRule::last_source, 32)->nullable();
            $table->timestamps();

            $table->unique(
                [
                    FixedCostMatchingRule::user_id,
                    FixedCostMatchingRule::fixed_cost_id,
                    FixedCostMatchingRule::fingerprint,
                ],
                'uq_fcmr_user_fixed_cost_fingerprint'
            );

            $table->index([FixedCostMatchingRule::user_id, FixedCostMatchingRule::fingerprint], 'idx_fcmr_user_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FixedCostMatchingRule::TABLE);
    }
};

