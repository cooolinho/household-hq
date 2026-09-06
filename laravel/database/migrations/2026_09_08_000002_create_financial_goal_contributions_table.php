<?php

use App\Models\Financial\Goal;
use App\Models\Financial\GoalContribution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(GoalContribution::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(GoalContribution::goal_id)
                ->constrained(Goal::TABLE, Goal::id, 'fgc_goal_id_foreign')
                ->cascadeOnDelete();
            $table->date(GoalContribution::date);
            $table->decimal(GoalContribution::amount, 15, 2);
            $table->string(GoalContribution::note)->nullable();
            $table->timestamps();

            $table->index([
                GoalContribution::goal_id,
                GoalContribution::date,
            ], 'fgc_goal_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(GoalContribution::TABLE);
    }
};
