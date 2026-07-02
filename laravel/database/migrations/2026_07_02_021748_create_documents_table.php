<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Document::TABLE, function (Blueprint $table) {
            $table->id();

            // relation to user
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            // document data
            $table->string(Document::type)->nullable(); // Vertrag, Rechnung, Sonstiges
            $table->string(Document::path);             // storage path oder URL
            $table->string(Document::filename)->nullable();
            $table->text(Document::description)->nullable();

            // file metadata (added)
            $table->unsignedBigInteger(Document::file_size)->nullable()->comment('size in bytes');
            $table->string(Document::mime_type)->nullable();

            // ordering
            $table->integer(Document::sort)->default(0)->index();

            // Polymorphe Relation hinzufügen
            $table->string(Document::documentable_type)->nullable();
            $table->unsignedBigInteger(Document::documentable_id)->nullable();

            $table->timestamps();

            $table->index([Document::user_id, Document::type]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Document::TABLE);
    }
};
