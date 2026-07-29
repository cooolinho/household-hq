<?php

use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(Transaction::TABLE, function (Blueprint $table) {
            $table->foreignIdFor(FixedCost::class, Transaction::fixed_cost_id)
                ->nullable()
                ->after(Transaction::hash)
                ->constrained(FixedCost::TABLE)
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(Transaction::TABLE, function (Blueprint $table) {
            $table->dropForeignIdFor(FixedCost::class, Transaction::fixed_cost_id);
            $table->dropColumn(Transaction::fixed_cost_id);
        });
    }
};

