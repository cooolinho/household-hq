<?php

use App\Models\Document;
use App\Models\ImportedEmail;
use App\Models\ImportedEmailAttachment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(ImportedEmailAttachment::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ImportedEmail::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Document::class)->nullable()->constrained()->nullOnDelete();

            $table->string(ImportedEmailAttachment::filename)->nullable();
            $table->string(ImportedEmailAttachment::extension)->nullable();
            $table->string(ImportedEmailAttachment::mime_type)->nullable();
            $table->unsignedBigInteger(ImportedEmailAttachment::size)->nullable();
            $table->string(ImportedEmailAttachment::status)->index();
            $table->string(ImportedEmailAttachment::skip_reason)->nullable();
            $table->text(ImportedEmailAttachment::error_message)->nullable();

            $table->timestamps();

            $table->index([ImportedEmailAttachment::imported_email_id, ImportedEmailAttachment::status]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ImportedEmailAttachment::TABLE);
    }
};

