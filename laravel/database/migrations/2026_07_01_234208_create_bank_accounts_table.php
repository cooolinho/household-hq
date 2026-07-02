<?php

use App\Models\BankAccount;
use App\Models\Enums\BankAccountTypeEnum;
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
        Schema::create(BankAccount::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(BankAccount::name);
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');
            $table->string(BankAccount::account_holder)->nullable();
            $table->string(BankAccount::iban)->nullable();
            $table->string(BankAccount::bic)->nullable();
            $table->string(BankAccount::bank_name)->nullable();
            $table->decimal(BankAccount::balance, 15, 2)->nullable();
            $table->dateTime(BankAccount::balance_date)->nullable();
            $table->enum(BankAccount::type, array_column(BankAccountTypeEnum::cases(), 'name'))->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(BankAccount::TABLE);
    }
};
