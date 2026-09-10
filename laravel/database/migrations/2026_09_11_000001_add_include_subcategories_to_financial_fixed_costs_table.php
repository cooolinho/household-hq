<?php

use App\Models\Financial\FixedCost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->boolean(FixedCost::include_subcategories)
                ->default(true)
                ->after(FixedCost::category_id);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->dropColumn(FixedCost::include_subcategories);
        });
    }
};
