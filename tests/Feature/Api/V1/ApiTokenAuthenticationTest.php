<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertJsonCount(2)
            ->assertJsonStructure(['token', 'token_type'])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // 不正な認証情報ではAPIトークンを発行できないことを確認する。
    #[DataProvider('invalidCredentialCases')]
    public function test_api_token_request_returns_unauthorized_for_invalid_credentials(
        ?string $email,
        string $password
    ): void {
        $user = User::factory()->create();

        $this->postJson('/api/v1/tokens', [
            'email' => $email ?? $user->email,
            'password' => $password,
        ])
            ->assertStatus(401)
            ->assertExactJson([
                'error' => 'メールアドレスまたはパスワードが正しくありません',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);

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
            ->assertExactJson([
                'error' => 'メールアドレスまたはパスワードが正しくありません',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // APIトークン発行時の必須項目を検証することを確認する。
    public function test_api_token_request_returns_validation_errors(): void
    {
        $this->postJson('/api/v1/tokens', [])
            ->assertStatus(422)
            ->assertExactJson([
                'error' => '入力内容に誤りがあります',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => [
                    'email' => ['メールアドレスを入力してください'],
                    'password' => ['パスワードを入力してください'],
                ],
            ]);
    }

    // 未認証ユーザーが保護された書籍APIを利用できないことを確認する。
    #[DataProvider('protectedBookEndpointCases')]
    public function test_guest_cannot_access_protected_book_endpoints(
        string $method,
        string $uri
    ): void {
        $this->json($method, $uri)
            ->assertUnauthorized()
            ->assertExactJson([
                'error' => '認証が必要です',
                'error_code' => 'AUTHENTICATION_REQUIRED',
            ]);
    }

    public static function invalidCredentialCases(): array
    {
        return [
            'unknown_email' => ['unknown@example.com', 'password'],
            'incorrect_password' => [null, 'incorrect-password'],
        ];
    }

    public static function protectedBookEndpointCases(): array
    {
        return [
            'store' => ['POST', '/api/v1/books'],
            'update' => ['PUT', '/api/v1/books/999999'],
            'destroy' => ['DELETE', '/api/v1/books/999999'],
        ];
    }
}
