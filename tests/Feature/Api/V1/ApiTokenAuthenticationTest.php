<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_issue_an_api_token_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/tokens', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['token', 'token_type'])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_api_token_request_returns_unauthorized_for_unknown_email(): void
    {
        $this->postJson('/api/tokens', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'メールアドレスまたはパスワードが正しくありません');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_token_request_returns_unauthorized_for_incorrect_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/tokens', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'メールアドレスまたはパスワードが正しくありません');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_token_request_returns_validation_errors(): void
    {
        $this->postJson('/api/tokens', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_guest_cannot_access_a_protected_api_endpoint(): void
    {
        $this->postJson('/api/books', [])
            ->assertStatus(401);
    }
}
