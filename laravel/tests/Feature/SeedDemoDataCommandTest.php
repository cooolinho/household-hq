<?php

namespace Tests\Feature;

use App\Models\Financial\Transaction;
use App\Models\User;
use App\Services\DemoSeederRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeedDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 5)->startOfDay());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_lists_seeders_and_supports_abort(): void
    {
        $this->artisan('app:seed-demo-data')
            ->expectsOutputToContain('Alle Demo-Daten')
            ->expectsOutputToContain('Gruppe Financial')
            ->expectsOutputToContain('Legt die Demo-Benutzer für die Entwicklung an')
            ->expectsQuestion('Welche Nummer möchtest du ausführen? (leer = Abbruch)', '')
            ->expectsOutput('Abgebrochen.')
            ->assertSuccessful();
    }

    public function test_it_runs_a_group_with_its_dependencies(): void
    {
        $choices = app(DemoSeederRunner::class)->choices();
        $choiceIndex = $choices->search(
            fn(array $choice): bool => $choice['kind'] === 'group'
                && $choice['group'] === 'Financial',
        );

        self::assertNotFalse($choiceIndex);

        $this->artisan('app:seed-demo-data')
            ->expectsQuestion(
                'Welche Nummer möchtest du ausführen? (leer = Abbruch)',
                (string)($choiceIndex + 1),
            )
            ->assertSuccessful();

        self::assertSame(2, User::query()->count());
        self::assertSame(167, Transaction::query()->count());
    }
}
