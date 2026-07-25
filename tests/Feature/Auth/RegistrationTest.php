<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_page(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertViewIs('auth.register');
    }

    public function test_guest_can_register_and_is_authenticated(): void
    {
        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
        ]);
    }

    public function test_authenticated_user_is_redirected_from_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $response = $this->post('/register', []);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    public function test_registration_validates_email_format(): void
    {
        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_validates_unique_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('password_confirmation');
        $this->assertGuest();
    }
}
