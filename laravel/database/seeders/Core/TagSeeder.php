<?php

namespace Database\Seeders\Core;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt stabile Demo-Tags an';
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
            $this->command?->warn('TagSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $locale = Tag::getLocale();
        $tags = [
            'Miete',
            'Strom',
            'Gehalt',
            'Versicherung',
            'Abonnement',
            'Einkauf',
            'Energie',
            'Wohnen',
            'Einnahme',
            'Ausgabe',
        ];

        foreach ($tags as $name) {
            Tag::query()
                ->where(Tag::user_id, $user->getKey())
                ->where("name->{$locale}", $name)
                ->firstOrCreate([], [
                    Tag::user_id => $user->getKey(),
                    Tag::name => $name,
                ]);
        }
    }
}
