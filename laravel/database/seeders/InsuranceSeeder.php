<?php

namespace Database\Seeders;

use App\Models\Financial\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = UserSeeder::getAdminUser();

        Insurance::query()->create([
            Insurance::name => 'Hausrat Versicherung',
            Insurance::user_id => $user->id,
        ]);

        Insurance::query()->create([
            Insurance::name => 'Muster Versicherung 2',
            Insurance::user_id => $user->id,
        ]);
    }
}
