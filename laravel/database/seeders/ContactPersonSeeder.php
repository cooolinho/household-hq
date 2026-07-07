<?php

namespace Database\Seeders;

use App\Models\ContactPerson;
use Illuminate\Database\Seeder;

class ContactPersonSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        ContactPerson::factory()
            ->count(3)
            ->forUser(UserSeeder::getAdminUser()->id)
            ->create();
    }
}
