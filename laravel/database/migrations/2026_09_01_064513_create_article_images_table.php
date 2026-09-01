<?php

use App\Models\Inventory\Article;
use App\Models\Inventory\ArticleImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ArticleImage::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Article::class)
                ->constrained(Article::TABLE)
                ->cascadeOnDelete();
            $table->string(ArticleImage::path);
            $table->unsignedInteger(ArticleImage::sort)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ArticleImage::TABLE);
    }
};
