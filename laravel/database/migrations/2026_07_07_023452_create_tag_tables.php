<?php

use App\Models\Contracts\Taggables;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(Tag::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');

            $table->json(Tag::name);
            $table->json(Tag::slug);
            $table->string(Tag::type)->nullable();
            $table->integer(Tag::order_column)->nullable();

            $table->timestamps();
        });

        Schema::create(Taggables::TABLE, function (Blueprint $table) {
            $table->foreignId(Taggables::tag_id)->constrained(Tag::TABLE)->cascadeOnDelete();
            $table->morphs(Taggables::MORPH_NAME);

            $table->unique([Taggables::tag_id, Taggables::taggable_id, Taggables::taggable_type]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Taggables::TABLE);
        Schema::dropIfExists(Tag::TABLE);
    }
};
