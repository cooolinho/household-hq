<?php

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
        Schema::create(User::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(User::name);
            $table->string(User::email)->unique();
            $table->timestamp(User::email_verified_at)->nullable();
            $table->string(User::password);
            $table->rememberToken();

            // Profile columns
            $table->string(User::firstname)->nullable();
            $table->string(User::lastname)->nullable();
            $table->string(User::date_of_birth)->nullable();
            $table->string(User::place_of_birth)->nullable();
            $table->string(User::address_street)->nullable();
            $table->string(User::address_street_number)->nullable();
            $table->string(User::address_zip)->nullable();
            $table->string(User::address_city)->nullable();
            $table->string(User::avatar)->nullable();
            $table->string(User::phone)->nullable();
            $table->string(User::email_private)->nullable();
            $table->string(User::email_business)->nullable();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(User::TABLE);
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
