<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_does_not_exist_when_disabled(): void
    {
        config(['auth.registration.enabled' => false]);

        $this->assertFalse(Filament::getPanel('app')->hasRegistration());
        $this->get('/app/register')->assertNotFound();
    }

    public function test_admin_panel_never_exposes_a_registration_route(): void
    {
        config(['auth.registration.enabled' => true]);

        $this->assertFalse(Filament::getPanel('admin')->hasRegistration());
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_registration_creates_an_unverified_regular_user_and_queues_the_verification_mail(): void
    {
        config(['auth.registration.enabled' => true]);

        Notification::fake();

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Neuer Benutzer',
                'email' => 'neu@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::query()->where(User::email, 'neu@example.com')->firstOrFail();

        $this->assertSame(Role::USER, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
