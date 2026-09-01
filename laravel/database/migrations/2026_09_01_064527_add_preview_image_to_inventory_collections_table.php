<?php

use App\Models\Inventory\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(Collection::TABLE, function (Blueprint $table) {
            $table->string(Collection::preview_image)->nullable()->after(Collection::name);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(Collection::TABLE, function (Blueprint $table) {
            $table->dropColumn(Collection::preview_image);
        });
    }
};
