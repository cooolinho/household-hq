<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\Insurance;
use App\Models\User;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt stabile Demo-Versicherungen an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [UserSeeder::class];
    }

    public function run(): void
    {
        $user = UserSeeder::getAppUser();

        if (!$user instanceof User) {
            $this->command?->warn('InsuranceSeeder: Admin-Benutzer fehlt.');

            return;
        }

        foreach (['Hausrat Versicherung', 'Muster Versicherung 2'] as $name) {
            Insurance::query()->firstOrCreate(
                [
                    Insurance::user_id => $user->getKey(),
                    Insurance::name => $name,
                ],
            );
        }
    }
}
