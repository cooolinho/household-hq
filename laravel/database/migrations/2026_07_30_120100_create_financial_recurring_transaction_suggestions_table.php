<?php

use App\Models\Enums\RecurringTransactionSuggestionStatusEnum;
use App\Models\Financial\RecurringTransactionSuggestion;
use App\Models\Financial\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(RecurringTransactionSuggestion::TABLE, function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, RecurringTransactionSuggestion::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();

            $table->string(RecurringTransactionSuggestion::fingerprint, 64);
            $table->string(RecurringTransactionSuggestion::payer)->nullable();
            $table->string(RecurringTransactionSuggestion::purpose)->nullable();
            $table->string(RecurringTransactionSuggestion::name_hint);
            $table->decimal(RecurringTransactionSuggestion::amount, 15, 2);
            $table->string(RecurringTransactionSuggestion::amount_currency)->nullable();
            $table->tinyInteger(RecurringTransactionSuggestion::amount_sign);
            $table->unsignedInteger(RecurringTransactionSuggestion::occurrence_count);
            $table->date(RecurringTransactionSuggestion::first_seen_at);
            $table->date(RecurringTransactionSuggestion::last_seen_at);

            $table->unsignedBigInteger(RecurringTransactionSuggestion::sample_transaction_id)
                ->nullable();
            $table->foreign(
                RecurringTransactionSuggestion::sample_transaction_id,
                'fk_ftrs_sample_transaction_id'
            )
                ->references(Transaction::id)
                ->on(Transaction::TABLE)
                ->nullOnDelete();

            $table->enum(RecurringTransactionSuggestion::status, RecurringTransactionSuggestionStatusEnum::allNames())
                ->default(RecurringTransactionSuggestionStatusEnum::default());

            $table->timestamps();

            $table->unique(
                [RecurringTransactionSuggestion::user_id, RecurringTransactionSuggestion::fingerprint],
                'uq_ftrs_user_fingerprint'
            );

            $table->index([RecurringTransactionSuggestion::user_id, RecurringTransactionSuggestion::status], 'idx_ftrs_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(RecurringTransactionSuggestion::TABLE);
    }
};

