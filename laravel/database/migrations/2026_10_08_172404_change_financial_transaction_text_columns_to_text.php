<?php

use App\Models\Financial\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(Transaction::TABLE, function (Blueprint $table): void {
            $table->longText(Transaction::payer)->nullable()->change();
            $table->longText(Transaction::description)->nullable()->change();
            $table->longText(Transaction::purpose)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([Transaction::payer, Transaction::description, Transaction::purpose] as $column) {
            if (DB::table(Transaction::TABLE)
                ->whereRaw('CHAR_LENGTH(`' . $column . '`) > 255')
                ->exists()) {
                throw new RuntimeException(
                    "Cannot shrink {$column} to VARCHAR(255) while values longer than 255 characters exist."
                );
            }
        }

        Schema::table(Transaction::TABLE, function (Blueprint $table): void {
            $table->string(Transaction::payer)->nullable()->change();
            $table->string(Transaction::description)->nullable()->change();
            $table->string(Transaction::purpose)->nullable()->change();
        });
    }
};
