<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    const string ADMIN_EMAIL = 'admin@example.com';

    public static function getAdminUser(): ?User
    {
        return User::query()
            ->where(User::email, UserSeeder::ADMIN_EMAIL)
            ->first();
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createUserIfMissing(self::ADMIN_EMAIL, 'Administrator');
        $this->createUserIfMissing('user@example.com', 'User');
    }

    private function createUserIfMissing(string $email, string $name): void
    {
        User::query()->firstOrCreate(
            [
                User::email => $email,
            ],
            [
                User::name => $name,
                User::email_verified_at => now(),
                User::password => 'secret',
            ],
        );
    }
}
