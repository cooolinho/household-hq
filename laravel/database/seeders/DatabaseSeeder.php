<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            User::name => 'Administrator',
            User::email => 'admin@example.com',
        ]);
        User::factory()->create([
            User::name => 'User',
            User::email => 'user@example.com',
        ]);
    }
}
