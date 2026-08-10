<?php

use App\Models\ImapAccount;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(ImapAccount::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->string(ImapAccount::name);
            $table->string(ImapAccount::host);
            $table->unsignedSmallInteger(ImapAccount::port)->default(993);
            $table->string(ImapAccount::encryption)->default('ssl');
            $table->string(ImapAccount::username);
            $table->text(ImapAccount::password);

            $table->string(ImapAccount::inbox_folder)->default('INBOX');
            $table->string(ImapAccount::processed_folder)->default('Processed');
            $table->boolean(ImapAccount::mark_as_read)->default(true);
            $table->boolean(ImapAccount::is_active)->default(true);
            $table->json(ImapAccount::allowed_extensions)->nullable();

            $table->timestamp(ImapAccount::last_run_at)->nullable();
            $table->text(ImapAccount::last_error)->nullable();

            $table->timestamps();

            $table->index([ImapAccount::user_id, ImapAccount::is_active]);
            $table->unique([ImapAccount::user_id, ImapAccount::name]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ImapAccount::TABLE);
    }
};

