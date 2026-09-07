<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string(User::role)->default(Role::USER->value)->after(User::email)->index();
            $table->boolean(User::is_active)->default(true)->after(User::role)->index();
        });

        // Bestehende Benutzer bleiben bewusst ROLE_USER (Spalten-Default) und
        // behalten damit ihren Zugriff auf das App-Panel. Wer ROLE_ADMIN
        // braucht (Zugriff auf /admin und /horizon), wird nach dem Deploy
        // gezielt per E-Mail befördert - siehe README "Nach dem Deploy".
        // Kein Bulk-Update hier: das würde auf einer Produktions-DB mit
        // echten Haushaltsmitgliedern jeden bestehenden Benutzer ungewollt
        // zum Admin machen.

        // Der User-Model implementiert ab jetzt MustVerifyEmail. Bestehende
        // Benutzer sollen dadurch nicht ausgesperrt werden.
        DB::table('users')
            ->whereNull(User::email_verified_at)
            ->update([
                User::email_verified_at => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([User::role, User::is_active]);
        });
    }
};
