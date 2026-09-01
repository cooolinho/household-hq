<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    const string ADMIN_EMAIL = 'admin@example.com';

    public static function description(): string
    {
        return 'Legt die Demo-Benutzer für die Entwicklung an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [];
    }

    public static function getAdminUser(): ?User
    {
        return User::query()
            ->where(User::email, self::ADMIN_EMAIL)
            ->first();
    }

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
