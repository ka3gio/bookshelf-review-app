<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    // 存在しないAPIルートがJSON形式の404を返すことを確認する。
    public function test_unknown_api_route_returns_a_json_error_response(): void
    {
        $this->getJson('/api/v1/unknown-endpoint')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    // 許可されていないHTTPメソッドがJSON形式の405を返すことを確認する。
    public function test_api_method_not_allowed_returns_a_json_error_response(): void
    {
        $this->deleteJson('/api/v1/tokens')
            ->assertStatus(405)
            ->assertJsonStructure(['message']);
    }
}
