<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ReadingPlanTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createReadingPlan(User $user, array $attributes = []): ReadingPlan
    {
        return ReadingPlan::create(array_replace([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create()->id,
            'target_date' => today()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::NotStarted,
            'completed_at' => null,
        ], $attributes));
    }

    public static function invalidTargetDateCases(): array
    {
        return [
            'missing date' => [null, '期日を入力してください'],
            'invalid date format' => ['not-a-date', '期日を正しい形式で入力してください'],
            'past date' => ['2026-07-25', '期日は今日以降の日付を指定してください'],
        ];
    }
}
