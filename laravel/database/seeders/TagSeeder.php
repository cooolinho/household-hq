<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Tag::factory()
            ->count(10)
            ->forUser(UserSeeder::getAdminUser()->id)
            ->create();
    }
}
