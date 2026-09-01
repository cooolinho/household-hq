<?php

use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(TransactionCategoryRule::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(TransactionCategoryRule::transaction_category_id);
            $table->foreign(TransactionCategoryRule::transaction_category_id, 'ftcr_category_id_foreign')
                ->references('id')
                ->on(TransactionCategory::TABLE)
                ->cascadeOnDelete();
            $table->string(TransactionCategoryRule::operator)->default('AND');
            $table->boolean(TransactionCategoryRule::active)->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TransactionCategoryRule::TABLE);
    }
};
