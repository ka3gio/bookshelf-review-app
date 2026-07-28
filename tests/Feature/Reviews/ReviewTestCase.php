<?php

namespace Tests\Feature\Reviews;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ReviewTestCase extends TestCase
{
    use RefreshDatabase;

    protected function reviewData(array $overrides = []): array
    {
        return array_replace([
            'rating' => 5,
            'comment' => 'とても参考になりました。',
        ], $overrides);
    }

    public static function invalidRatingCases(): array
    {
        return [
            'missing rating' => [null],
            'below minimum' => [0],
            'above maximum' => [6],
            'non integer' => ['2.5'],
        ];
    }
}
