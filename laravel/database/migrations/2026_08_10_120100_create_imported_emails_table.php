<?php

use App\Models\ImapAccount;
use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(ImportedEmail::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ImapAccount::class)->constrained()->cascadeOnDelete();

            $table->string(ImportedEmail::message_id)->nullable();
            $table->unsignedBigInteger(ImportedEmail::uid)->nullable();
            $table->string(ImportedEmail::subject)->nullable();
            $table->string(ImportedEmail::from_email)->nullable();
            $table->string(ImportedEmail::from_name)->nullable();
            $table->timestamp(ImportedEmail::received_at)->nullable();
            $table->string(ImportedEmail::mailbox_folder)->default('INBOX');
            $table->longText(ImportedEmail::raw_headers)->nullable();

            $table->boolean(ImportedEmail::marked_as_read)->default(false);
            $table->boolean(ImportedEmail::moved_to_processed)->default(false);
            $table->unsignedInteger(ImportedEmail::warning_count)->default(0);
            $table->text(ImportedEmail::warning_summary)->nullable();
            $table->timestamp(ImportedEmail::processed_at)->nullable();

            $table->timestamps();

            $table->index([ImportedEmail::user_id, ImportedEmail::received_at]);
            $table->index([ImportedEmail::imap_account_id, ImportedEmail::uid]);
            $table->unique([ImportedEmail::imap_account_id, ImportedEmail::message_id]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ImportedEmail::TABLE);
    }
};

