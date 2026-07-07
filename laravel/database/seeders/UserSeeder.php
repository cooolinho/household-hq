<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    const string ADMIN_EMAIL = 'admin@example.com';

    /**
     * @return User|null
     */
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
        User::factory()->create([
            User::name => 'Administrator',
            User::email => self::ADMIN_EMAIL,
        ]);
        User::factory()->create([
            User::name => 'User',
            User::email => 'user@example.com',
        ]);
    }
}
