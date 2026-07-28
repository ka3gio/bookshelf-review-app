<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ゲストが会員登録画面を表示できることを確認する。
    public function test_guest_can_view_registration_page(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertViewIs('auth.register');
    }

    // 会員登録後に認証済み状態になることを確認する。
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

    // 認証済みユーザーが登録画面からホームへ戻されることを確認する。
    public function test_authenticated_user_is_redirected_from_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertStatus(302)
            ->assertRedirect(RouteServiceProvider::HOME);
    }

    // 会員登録の必須項目を検証することを確認する。
    public function test_registration_requires_name_email_and_password(): void
    {
        $response = $this->post('/register', []);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    // 会員登録時にメールアドレス形式を検証することを確認する。
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

    // 会員登録時にメールアドレスの重複を拒否することを確認する。
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

    // パスワード確認が一致しない会員登録を拒否することを確認する。
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

    // ユーザー名の上限255文字を受理することを確認する。
    public function test_registration_accepts_name_at_the_maximum_length(): void
    {
        $name = str_repeat('名', 255);

        $this->post('/register', [
            'name' => $name,
            'email' => 'boundary@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', ['name' => $name]);
    }

    // ユーザー名とメールアドレスが255文字を超える場合に拒否されることを確認する。
    public function test_registration_rejects_name_and_email_over_the_maximum_length(): void
    {
        $this->post('/register', [
            'name' => str_repeat('n', 256),
            'email' => str_repeat('e', 244).'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['name', 'email']);
    }

    // パスワードの最小8文字を受理し7文字を拒否することを確認する。
    public function test_registration_validates_password_length_boundary(): void
    {
        $this->post('/register', [
            'name' => '境界値ユーザー',
            'email' => 'short-password@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => '境界値ユーザー',
            'email' => 'valid-password@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertSessionDoesntHaveErrors();
    }
}
