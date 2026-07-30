<?php

use App\Models\Financial\FixedCost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->string(FixedCost::notes, 255)
                ->nullable()
                ->after(FixedCost::name);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->dropColumn(FixedCost::notes);
        });
    }
};

