<?php

use App\Models\ImapAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(ImapAccount::TABLE, function (Blueprint $table) {
            $table->json(ImapAccount::blacklisted_senders)->nullable();
            $table->json(ImapAccount::blacklisted_subject_keywords)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(ImapAccount::TABLE, function (Blueprint $table) {
            $table->dropColumn([ImapAccount::blacklisted_senders, ImapAccount::blacklisted_subject_keywords]);
        });
    }
};
