<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_can_access_the_app_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/app')->assertOk();
    }

    public function test_normal_user_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_both_panels(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/app')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_guest_is_redirected_to_the_central_login_when_visiting_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertRedirect('/app/login');
    }

    public function test_blocked_admin_cannot_access_either_panel(): void
    {
        $admin = User::factory()->admin()->blocked()->create();

        $this->actingAs($admin)->get('/app')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }
}
