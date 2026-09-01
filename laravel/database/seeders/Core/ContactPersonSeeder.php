<?php

namespace Database\Seeders\Core;

use App\Models\ContactPerson;
use App\Models\Enums\ContactPersonTypeEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContactPersonSeeder extends Seeder
{
    public static function description(): string
    {
        return 'Legt stabile Demo-Kontaktpersonen an';
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
        $user = UserSeeder::getAdminUser();

        if (!$user instanceof User) {
            $this->command?->warn('ContactPersonSeeder: Admin-Benutzer fehlt.');

            return;
        }

        $contacts = [
            [
                ContactPerson::email => 'anna.mueller@example.com',
                ContactPerson::title => 'Frau',
                ContactPerson::firstname => 'Anna',
                ContactPerson::lastname => 'Müller',
                ContactPerson::phone_private => '+49 170 1234567',
                ContactPerson::phone_business => null,
                ContactPerson::role => 'Private Ansprechpartnerin',
                ContactPerson::type => ContactPersonTypeEnum::PRIVATE->name,
            ],
            [
                ContactPerson::email => 'markus.schneider@stadtwerke.example',
                ContactPerson::title => 'Herr',
                ContactPerson::firstname => 'Markus',
                ContactPerson::lastname => 'Schneider',
                ContactPerson::phone_private => null,
                ContactPerson::phone_business => '+49 89 9876543',
                ContactPerson::role => 'Kundenbetreuung',
                ContactPerson::type => ContactPersonTypeEnum::BUSINESS->name,
            ],
            [
                ContactPerson::email => 'lisa.wagner@example.com',
                ContactPerson::title => 'Frau',
                ContactPerson::firstname => 'Lisa',
                ContactPerson::lastname => 'Wagner',
                ContactPerson::phone_private => '+49 171 7654321',
                ContactPerson::phone_business => null,
                ContactPerson::role => 'Private Ansprechpartnerin',
                ContactPerson::type => ContactPersonTypeEnum::PRIVATE->name,
            ],
        ];

        foreach ($contacts as $contact) {
            ContactPerson::query()->firstOrCreate(
                [
                    ContactPerson::user_id => $user->getKey(),
                    ContactPerson::email => $contact[ContactPerson::email],
                ],
                $contact,
            );
        }
    }
}
