<?php

use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    const string PIVOT_TABLE = 'financial_transaction_transaction_category';

    public function up(): void
    {
        Schema::create(self::PIVOT_TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id', 'fttc_transaction_id_foreign')
                ->references('id')
                ->on(Transaction::TABLE)
                ->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_category_id');
            $table->foreign('transaction_category_id', 'fttc_category_id_foreign')
                ->references('id')
                ->on(TransactionCategory::TABLE)
                ->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['transaction_id', 'transaction_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::PIVOT_TABLE);
    }
};
