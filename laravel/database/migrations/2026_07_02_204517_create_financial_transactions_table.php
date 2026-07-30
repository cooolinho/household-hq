<?php

use App\Models\Financial\BankAccount;
use App\Models\Financial\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Transaction::TABLE, function (Blueprint $table) {
            $table->id();
            $table->date(Transaction::date);
            $table->date(Transaction::value_date)->nullable();
            $table->string(Transaction::payer)->nullable();
            $table->string(Transaction::description)->nullable();
            $table->string(Transaction::purpose)->nullable();
            $table->decimal(Transaction::balance, 15, 2)->nullable();
            $table->string(Transaction::balance_currency)->nullable();
            $table->decimal(Transaction::amount, 15, 2);
            $table->string(Transaction::amount_currency)->nullable();
            $table->string(Transaction::hash);

            $table->foreignIdFor(BankAccount::class, Transaction::bank_account_id)
                ->constrained(BankAccount::TABLE)
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, Transaction::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([Transaction::hash]);
            $table->index([Transaction::hash]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Transaction::TABLE);
    }
};
