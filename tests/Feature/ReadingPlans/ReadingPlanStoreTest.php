<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class ReadingPlanStoreTest extends ReadingPlanTestCase
{
    // 認証済みユーザーが書籍の読書計画を未開始状態で作成できることを確認する。
    public function test_authenticated_user_can_create_a_plan_for_a_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = today()->addMonth()->toDateString();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionDoesntHaveErrors();
        $plan = ReadingPlan::whereBelongsTo($user)
            ->whereBelongsTo($book)
            ->firstOrFail();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::NotStarted->value,
            'completed_at' => null,
        ]);
        $this->assertSame($targetDate, $plan->target_date->toDateString());
    }

    // ゲストが読書計画を作成できずログイン画面へ誘導されることを確認する。
    public function test_guest_cannot_create_a_reading_plan(): void
    {
        $book = Book::factory()->create();

        $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => today()->addDay()->toDateString(),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('reading_plans', 0);
    }

    // 読書計画の書籍が未指定の場合にバリデーションエラーとなることを確認する。
    public function test_store_requires_a_book(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reading-plans.store'), [
                'target_date' => today()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['book_id' => '書籍を選択してください']);
    }

    // 存在しない書籍を指定した読書計画を作成できないことを確認する。
    public function test_store_rejects_a_nonexistent_book(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reading-plans.store'), [
                'book_id' => 999999,
                'target_date' => today()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['book_id' => '書籍が存在しません']);
    }

    // 読書計画の期日が未入力、不正形式、過去日の場合に拒否されることを確認する。
    #[DataProviderExternal(ReadingPlanTestCase::class, 'invalidTargetDateCases')]
    public function test_store_validates_the_target_date(
        ?string $targetDate,
        string $message
    ): void {
        $this->travelTo('2026-07-26 12:00:00');
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ])
            ->assertSessionHasErrors(['target_date' => $message]);

        $this->assertDatabaseCount('reading_plans', 0);
    }
}
