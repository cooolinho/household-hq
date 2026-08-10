<?php

use App\Models\ApplicationLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(ApplicationLog::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->string(ApplicationLog::event)->index();
            $table->string(ApplicationLog::channel)->default('database')->index();
            $table->string(ApplicationLog::level)->index();
            $table->text(ApplicationLog::message);
            $table->json(ApplicationLog::context)->nullable();
            $table->timestamp(ApplicationLog::occurred_at)->useCurrent()->index();
            $table->timestamps();

            $table->index([ApplicationLog::user_id, ApplicationLog::occurred_at]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ApplicationLog::TABLE);
    }
};

