<?php

use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Location::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Collection::class)
                ->constrained()
                ->onDelete('cascade');

            $table->string(Location::name);
            $table->text(Location::description)->nullable();
            $table->string(Location::preview_image)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Location::TABLE);
    }
};
