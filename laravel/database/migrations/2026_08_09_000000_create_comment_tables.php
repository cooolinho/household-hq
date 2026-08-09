<?php

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(Comment::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');

            $table->morphs('commentable'); // commentable_type + commentable_id + index

            $table->text(Comment::message);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Comment::TABLE);
    }
};
