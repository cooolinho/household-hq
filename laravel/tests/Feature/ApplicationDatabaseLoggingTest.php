<?php

namespace Tests\Feature;

use App\Models\ApplicationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApplicationDatabaseLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_log_entries_to_database_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Log::channel('database')->info('Testeintrag', [
            'event' => 'test.event',
            'meta' => ['foo' => 'bar'],
        ]);

        $this->assertDatabaseHas(ApplicationLog::TABLE, [
            ApplicationLog::event => 'test.event',
            ApplicationLog::message => 'Testeintrag',
            ApplicationLog::user_id => $user->id,
            ApplicationLog::channel => 'database',
            ApplicationLog::level => 'info',
        ]);
    }

    public function test_it_redacts_sensitive_context_values(): void
    {
        Log::channel('database')->info('Sensitive Daten', [
            'event' => 'security.check',
            'password' => 'my-secret-password',
            'nested' => [
                'access_token' => 'abc123',
            ],
        ]);

        $log = ApplicationLog::query()->where(ApplicationLog::event, 'security.check')->firstOrFail();

        $this->assertSame('[redacted]', $log->{ApplicationLog::context}['password']);
        $this->assertSame('[redacted]', $log->{ApplicationLog::context}['nested']['access_token']);
    }
}

