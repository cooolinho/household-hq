<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_cannot_access_horizon(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/horizon')->assertForbidden();
    }

    public function test_admin_can_access_horizon(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/horizon')->assertOk();
    }

    public function test_blocked_admin_cannot_access_horizon(): void
    {
        $admin = User::factory()->admin()->blocked()->create();

        $this->actingAs($admin)->get('/horizon')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_visiting_horizon(): void
    {
        $this->get('/horizon')->assertRedirect('/login');
    }
}
