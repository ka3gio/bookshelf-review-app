<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    // 存在しないAPIルートが共通エラー形式を返すことを確認する。
    public function test_unknown_api_route_returns_a_localized_error_response(): void
    {
        $this->getJson('/api/v1/unknown-endpoint')
            ->assertNotFound()
            ->assertExactJson([
                'error' => '指定されたリソースが見つかりません',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    // 許可されていないHTTPメソッドが共通エラー形式を返すことを確認する。
    public function test_api_method_not_allowed_returns_a_localized_error_response(): void
    {
        $this->deleteJson('/api/v1/tokens')
            ->assertStatus(405)
            ->assertExactJson([
                'error' => 'このHTTPメソッドは許可されていません',
                'error_code' => 'METHOD_NOT_ALLOWED',
            ]);
    }
}
