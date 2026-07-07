<?php

use App\Models\ContactPerson;
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
        Schema::create(ContactPerson::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade');

            $table->string(ContactPerson::title)->nullable();
            $table->string(ContactPerson::firstname);
            $table->string(ContactPerson::lastname);
            $table->string(ContactPerson::phone_private)->nullable();
            $table->string(ContactPerson::phone_business)->nullable();
            $table->string(ContactPerson::email)->nullable();
            $table->text(ContactPerson::notes)->nullable();
            $table->string(ContactPerson::avatar)->nullable();
            $table->string(ContactPerson::role)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ContactPerson::TABLE);
    }
};
