<?php

use App\Models\Enums\FixedCostCategoryEnum;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
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
        Schema::create(FixedCost::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');
            $table->string(FixedCost::name);
            $table->decimal(FixedCost::amount, 10, 2);
            $table->enum(FixedCost::category, FixedCostCategoryEnum::allNames())
                ->default(FixedCostCategoryEnum::default());
            $table->enum(FixedCost::interval, FixedCostIntervalEnum::allNames());
            $table->enum(FixedCost::ends_mode, FixedCostEndsModeEnum::allNames())
                ->default(FixedCostEndsModeEnum::default());
            $table->date(FixedCost::ends_date)->nullable();
            $table->enum(FixedCost::ends_interval, FixedCostIntervalEnum::allNames())
                ->default(FixedCostIntervalEnum::default());
            $table->date(FixedCost::extended_date)->nullable();
            $table->enum(FixedCost::extended_interval, FixedCostIntervalEnum::allNames())
                ->default(FixedCostIntervalEnum::default());

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(FixedCost::TABLE);
    }
};
