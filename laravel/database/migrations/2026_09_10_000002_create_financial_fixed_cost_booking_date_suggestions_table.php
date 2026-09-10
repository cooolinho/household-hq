<?php

use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostBookingDateSuggestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FixedCostBookingDateSuggestion::TABLE, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(FixedCostBookingDateSuggestion::fixed_cost_id);
            $table->foreign(FixedCostBookingDateSuggestion::fixed_cost_id, 'fk_fcbds_fixed_cost_id')
                ->references('id')
                ->on(FixedCost::TABLE)
                ->cascadeOnDelete();

            $table->unsignedTinyInteger(FixedCostBookingDateSuggestion::suggested_day);
            $table->date(FixedCostBookingDateSuggestion::suggested_date);
            $table->date(FixedCostBookingDateSuggestion::current_date)->nullable();
            $table->unsignedTinyInteger(FixedCostBookingDateSuggestion::deviation_days);
            $table->unsignedInteger(FixedCostBookingDateSuggestion::sample_count);
            $table->date(FixedCostBookingDateSuggestion::analyzed_from);
            $table->date(FixedCostBookingDateSuggestion::analyzed_to);
            $table->enum(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::allNames())
                ->default(MatchingSuggestionStatusEnum::PENDING->name);
            $table->timestamps();

            $table->unique(
                [FixedCostBookingDateSuggestion::fixed_cost_id, FixedCostBookingDateSuggestion::suggested_day],
                'uq_fcbds_fixed_cost_day'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FixedCostBookingDateSuggestion::TABLE);
    }
};
