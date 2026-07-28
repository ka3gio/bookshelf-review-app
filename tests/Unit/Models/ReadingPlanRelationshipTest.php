<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // 読書計画から所有ユーザーを取得できることを確認する。
    public function test_reading_plan_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($plan->user->is($user));
    }

    // 読書計画から対象書籍を取得できることを確認する。
    public function test_reading_plan_belongs_to_a_book(): void
    {
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $this->assertTrue($plan->book->is($book));
    }

    // 読書計画の期日と読了日が日付オブジェクトへキャストされることを確認する。
    public function test_reading_plan_dates_are_cast_to_date_objects(): void
    {
        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-08-01',
            'completed_at' => '2026-07-27',
        ]);

        $this->assertInstanceOf(CarbonInterface::class, $plan->target_date);
        $this->assertInstanceOf(CarbonInterface::class, $plan->completed_at);
    }

    // 読書計画のステータスが列挙型へキャストされることを確認する。
    public function test_reading_plan_status_is_cast_to_enum(): void
    {
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);
    }
}
