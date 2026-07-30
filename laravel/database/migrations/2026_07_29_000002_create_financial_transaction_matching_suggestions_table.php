<?php

use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionMatchingSuggestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(TransactionMatchingSuggestion::TABLE, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(TransactionMatchingSuggestion::transaction_id);
            $table->foreign(TransactionMatchingSuggestion::transaction_id, 'fk_tms_transaction_id')
                ->references('id')
                ->on(Transaction::TABLE)
                ->cascadeOnDelete();

            $table->unsignedBigInteger(TransactionMatchingSuggestion::fixed_cost_id);
            $table->foreign(TransactionMatchingSuggestion::fixed_cost_id, 'fk_tms_fixed_cost_id')
                ->references('id')
                ->on(FixedCost::TABLE)
                ->cascadeOnDelete();

            $table->decimal(TransactionMatchingSuggestion::score, 5, 2);
            $table->enum(TransactionMatchingSuggestion::status, MatchingSuggestionStatusEnum::allNames())
                ->default(MatchingSuggestionStatusEnum::PENDING->name);
            $table->timestamps();

            $table->unique(
                [TransactionMatchingSuggestion::transaction_id, TransactionMatchingSuggestion::fixed_cost_id],
                'uq_tms_transaction_fixed_cost'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TransactionMatchingSuggestion::TABLE);
    }
};
