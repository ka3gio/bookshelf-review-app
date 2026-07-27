<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // 正しい認証情報でAPIトークンを発行できることを確認する。
    public function test_user_can_issue_an_api_token_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['token', 'token_type'])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // 未登録メールアドレスではAPIトークンを発行できないことを確認する。
    public function test_api_token_request_returns_unauthorized_for_unknown_email(): void
    {
        $this->postJson('/api/v1/tokens', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'メールアドレスまたはパスワードが正しくありません');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // 誤ったパスワードではAPIトークンを発行できないことを確認する。
    public function test_api_token_request_returns_unauthorized_for_incorrect_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'メールアドレスまたはパスワードが正しくありません');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // Acceptヘッダーがなくても認証失敗時はJSON形式の401を返すことを確認する。
    public function test_api_token_request_returns_json_unauthorized_without_accept_header(): void
    {
        $this->post('/api/v1/tokens', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('message', 'メールアドレスまたはパスワードが正しくありません');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // APIトークン発行時の必須項目を検証することを確認する。
    public function test_api_token_request_returns_validation_errors(): void
    {
        $this->postJson('/api/v1/tokens', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // 未認証ユーザーが保護されたAPIを利用できないことを確認する。
    public function test_guest_cannot_access_a_protected_api_endpoint(): void
    {
        $this->postJson('/api/v1/books', [])
            ->assertStatus(401);
    }

    // 未認証ユーザーが保護された書籍更新APIを利用できないことを確認する。
    public function test_guest_cannot_update_a_book_through_the_api(): void
    {
        $this->putJson('/api/v1/books/999999', [])
            ->assertUnauthorized();
    }

    // 未認証ユーザーが保護された書籍削除APIを利用できないことを確認する。
    public function test_guest_cannot_delete_a_book_through_the_api(): void
    {
        $this->deleteJson('/api/v1/books/999999')
            ->assertUnauthorized();
    }
}
