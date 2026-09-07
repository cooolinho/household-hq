<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Livewire::test() geht nicht über die HTTP-Middleware, die sonst das
        // aktuelle Panel anhand der URL setzt - ohne das hier explizit zu tun,
        // würde Filament auf das Default-Panel ("app") zurückfallen und alle
        // Resource-URLs im Admin-Panel falsch auflösen.
        Filament::setCurrentPanel('admin');
    }

    public function test_resend_verification_email_uses_the_app_panel_route_not_the_admin_panel(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $target = User::factory()->unverified()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('resendVerificationEmail', $target);

        Notification::assertSentTo(
            $target,
            VerifyEmail::class,
            function (VerifyEmail $notification) {
                return str_contains($notification->url, '/app/email-verification/verify/');
            },
        );
    }

    public function test_resend_verification_email_is_hidden_for_already_verified_users(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('resendVerificationEmail', $target);
    }

    public function test_admin_can_block_and_unblock_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleActive', $target);

        $this->assertFalse($target->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleActive', $target);

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_cannot_block_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $component = Livewire::actingAs($admin)->test(ListUsers::class);

        $component->assertTableActionHidden('toggleActive', $admin);
        $component->assertTableActionHidden('delete', $admin);
    }

    public function test_creating_a_user_with_verification_toggle_on_marks_the_email_as_verified(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                User::name => 'Manuell Angelegt',
                User::email => 'manuell@example.com',
                User::password => 'password',
                User::role => Role::USER->value,
                User::is_active => true,
                UserForm::FIELD_MARK_EMAIL_VERIFIED => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where(User::email, 'manuell@example.com')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_creating_a_user_with_verification_toggle_off_sends_the_verification_mail(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                User::name => 'Muss Verifizieren',
                User::email => 'verify-me@example.com',
                User::password => 'password',
                User::role => Role::USER->value,
                User::is_active => true,
                UserForm::FIELD_MARK_EMAIL_VERIFIED => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where(User::email, 'verify-me@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
