<?php

use App\Models\Enums\GoalDirectionEnum;
use App\Models\Enums\GoalIconEnum;
use App\Models\Enums\GoalTypeEnum;
use App\Models\Financial\Goal;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(Goal::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, Goal::user_id)
                ->constrained(User::TABLE)
                ->cascadeOnDelete();
            $table->string(Goal::name);
            $table->text(Goal::description)->nullable();
            $table->enum(Goal::icon, GoalIconEnum::allNames())
                ->default(GoalIconEnum::default());
            $table->enum(Goal::type, GoalTypeEnum::allNames())
                ->default(GoalTypeEnum::default());
            $table->enum(Goal::direction, GoalDirectionEnum::allNames())
                ->default(GoalDirectionEnum::default());
            $table->decimal(Goal::start_amount, 15, 2)->default(0);
            $table->decimal(Goal::target_amount, 15, 2);
            $table->string(Goal::currency, 3)->default(Goal::DEFAULT_CURRENCY);
            $table->boolean(Goal::include_subcategories)->default(true);
            $table->date(Goal::start_date);
            $table->date(Goal::target_date)->nullable();
            $table->string(Goal::image_path)->nullable();
            $table->boolean(Goal::active)->default(true);
            $table->unsignedInteger(Goal::sort)->default(0);
            $table->timestamps();

            $table->index([
                Goal::user_id,
                Goal::active,
                Goal::sort,
            ], 'fg_user_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Goal::TABLE);
    }
};
