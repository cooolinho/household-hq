<?php

namespace Tests\Unit;

use App\Services\DemoSeederRunner;
use Database\Seeders\Core\UserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EnergyTracker\EnergyTrackerSeeder;
use Database\Seeders\Financial\BankAccountSeeder;
use Database\Seeders\Financial\TransactionSeeder;
use Tests\TestCase;

class DemoSeederRunnerTest extends TestCase
{
    public function test_it_discovers_seeders_with_descriptions_and_groups(): void
    {
        $seeders = app(DemoSeederRunner::class)->all();

        self::assertCount(14, $seeders);
        self::assertSame(DatabaseSeeder::class, $seeders->first()['class']);
        self::assertContains(UserSeeder::class, $seeders->pluck('class')->all());
        self::assertContains(EnergyTrackerSeeder::class, $seeders->pluck('class')->all());
        self::assertContains(BankAccountSeeder::class, $seeders->pluck('class')->all());

        self::assertSame(
            ['Core', 'EnergyTracker', 'Financial'],
            $seeders
                ->reject(fn(array $seeder): bool => $seeder['class'] === DatabaseSeeder::class)
                ->pluck('group')
                ->unique()
                ->values()
                ->all(),
        );

        foreach ($seeders as $seeder) {
            self::assertNotSame('', $seeder['description']);
        }
    }

    public function test_it_exposes_all_and_group_choices(): void
    {
        $choices = app(DemoSeederRunner::class)->choices();

        self::assertSame('all', $choices->first()['kind']);
        self::assertSame(
            ['Core', 'EnergyTracker', 'Financial'],
            $choices
                ->where('kind', 'group')
                ->pluck('key')
                ->values()
                ->all(),
        );
        self::assertContains(
            TransactionSeeder::class,
            $choices
                ->where('kind', 'seeder')
                ->pluck('class')
                ->all(),
        );
    }
}
