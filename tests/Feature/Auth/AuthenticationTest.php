<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertViewIs('auth.login');
    }

    public function test_guest_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->post('/login', []);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
