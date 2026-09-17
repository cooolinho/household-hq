<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_user(): void
    {
        $this->artisan('app:create-admin-user', [
            '--name' => 'Chefadmin',
            '--email' => 'chef@example.com',
            '--password' => 'super-secret',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $user = User::query()->where(User::email, 'chef@example.com')->firstOrFail();

        self::assertSame('Chefadmin', $user->name);
        self::assertSame(Role::ADMIN, $user->role);
        self::assertTrue($user->is_active);
        self::assertNotNull($user->email_verified_at);
        self::assertTrue(Hash::check('super-secret', $user->password));
    }

    public function test_it_leaves_an_existing_user_untouched(): void
    {
        $this->artisan('app:create-admin-user', [
            '--email' => 'chef@example.com',
            '--password' => 'first-password',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $originalHash = User::query()->where(User::email, 'chef@example.com')->value(User::password);

        $this->artisan('app:create-admin-user', [
            '--email' => 'chef@example.com',
            '--password' => 'second-password',
            '--no-interaction' => true,
        ])->assertSuccessful();

        self::assertSame(1, User::query()->where(User::email, 'chef@example.com')->count());
        self::assertSame(
            $originalHash,
            User::query()->where(User::email, 'chef@example.com')->value(User::password),
        );
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('app:create-admin-user', [
            '--email' => 'not-an-email',
            '--password' => 'super-secret',
            '--no-interaction' => true,
        ])->assertFailed();

        self::assertSame(0, User::query()->count());
    }

    public function test_it_requires_a_password_without_interaction(): void
    {
        $this->artisan('app:create-admin-user', [
            '--email' => 'chef@example.com',
            '--no-interaction' => true,
        ])->assertFailed();

        self::assertSame(0, User::query()->count());
    }
}
