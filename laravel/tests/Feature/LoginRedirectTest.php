<?php

namespace Tests\Feature;

use App\Filament\Auth\Pages\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'password';

    public function test_admin_is_redirected_to_the_admin_panel_after_login(): void
    {
        $admin = User::factory()->admin()->create([
            User::password => self::PASSWORD,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => self::PASSWORD,
            ])
            ->call('authenticate')
            ->assertRedirect('/admin');
    }

    public function test_normal_user_is_redirected_to_the_app_panel_after_login(): void
    {
        $user = User::factory()->create([
            User::password => self::PASSWORD,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => self::PASSWORD,
            ])
            ->call('authenticate')
            ->assertRedirect('/app');
    }

    public function test_normal_user_with_an_intended_admin_url_still_lands_on_the_app_panel(): void
    {
        $user = User::factory()->create([
            User::password => self::PASSWORD,
        ]);

        session()->put('url.intended', 'http://localhost/admin/users');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => self::PASSWORD,
            ])
            ->call('authenticate')
            ->assertRedirect('/app');
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        $user = User::factory()->blocked()->create([
            User::password => self::PASSWORD,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => self::PASSWORD,
            ])
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }
}
