<?php

use App\Models\Financial\FixedCostCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(FixedCostCategory::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(FixedCostCategory::name);
            $table->string(FixedCostCategory::group)->nullable();
            $table->timestamps();

            $table->unique([FixedCostCategory::group, FixedCostCategory::name], 'ffc_group_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(FixedCostCategory::TABLE);
    }
};

