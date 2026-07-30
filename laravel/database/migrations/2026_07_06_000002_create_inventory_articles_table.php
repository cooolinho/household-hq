<?php

use App\Models\Inventory\Article;
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
        Schema::create(Article::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(Article::name);
            $table->text(Article::description)->nullable();
            $table->integer(Article::amount)->nullable();
            $table->string(Article::serial_number)->nullable();
            $table->string(Article::model_number)->nullable();
            $table->string(Article::manufacturer)->nullable();
            $table->text(Article::notes)->nullable();
            $table->boolean(Article::insured)->nullable();
            $table->boolean(Article::archived)->nullable();
            $table->float(Article::purchase_price)->nullable();
            $table->string(Article::purchase_place)->nullable();
            $table->date(Article::purchase_date)->nullable();
            $table->integer(Article::warranty_lifetime)->nullable();
            $table->date(Article::warranty_until)->nullable();
            $table->text(Article::warranty_details)->nullable();
            $table->float(Article::sale_price)->nullable();
            $table->string(Article::sold_to)->nullable();
            $table->date(Article::sold_at)->nullable();

            $table->foreignIdFor(Article::class, Article::parent_id)
                ->nullable()
                ->constrained(Article::TABLE)
                ->onDelete('cascade');
            $table->foreignIdFor(Location::class)
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Article::TABLE);
    }
};
