<?php

use App\Models\Financial\InsuranceCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(InsuranceCategory::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(InsuranceCategory::name);
            $table->string(InsuranceCategory::group);
            $table->timestamps();

            $table->unique([InsuranceCategory::group, InsuranceCategory::name], 'fic_group_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(InsuranceCategory::TABLE);
    }
};

