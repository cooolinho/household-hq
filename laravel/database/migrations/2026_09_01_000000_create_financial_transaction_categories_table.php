<?php

use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(TransactionCategory::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, TransactionCategory::user_id)
                ->constrained()
                ->cascadeOnDelete();
            $table->string(TransactionCategory::name);
            $table->foreignId(TransactionCategory::parent_id)
                ->nullable()
                ->constrained(TransactionCategory::TABLE)
                ->nullOnDelete();
            $table->boolean(TransactionCategory::active)->default(true);
            $table->timestamps();

            $table->unique([
                TransactionCategory::user_id,
                TransactionCategory::name,
                TransactionCategory::parent_id,
            ], 'ftc_user_name_parent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TransactionCategory::TABLE);
    }
};
