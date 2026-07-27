<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceNotFoundTest extends TestCase
{
    use RefreshDatabase;

    // 存在しない書籍の表示が404となることを確認する。
    public function test_nonexistent_book_returns_not_found(): void
    {
        $this->get(route('books.show', 999999))
            ->assertNotFound();
    }

    // 存在しないレビューの編集が404となることを確認する。
    public function test_nonexistent_review_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit', 999999))
            ->assertNotFound();
    }

    // 存在しないジャンルの表示が404となることを確認する。
    public function test_nonexistent_genre_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('genres.show', 999999))
            ->assertNotFound();
    }

    // 存在しない読書計画の編集が404となることを確認する。
    public function test_nonexistent_reading_plan_edit_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reading-plans.edit', 999999))
            ->assertNotFound();
    }

    // 存在しない読書計画の更新が404となることを確認する。
    public function test_nonexistent_reading_plan_update_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('reading-plans.update', 999999), [
                'target_date' => today()->addDay()->toDateString(),
            ])
            ->assertNotFound();
    }

    // 存在しない読書計画の削除が404となることを確認する。
    public function test_nonexistent_reading_plan_delete_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('reading-plans.destroy', 999999))
            ->assertNotFound();
    }

    // 存在しない通知の既読操作が404となることを確認する。
    public function test_nonexistent_notification_read_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('notifications.read', 'missing-notification'))
            ->assertNotFound();
    }

    // 存在しない書籍へのレビュー投稿が404となることを確認する。
    public function test_review_store_for_a_nonexistent_book_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', 999999), [
                'rating' => 5,
                'comment' => '存在しない書籍へのレビュー',
            ])
            ->assertNotFound();
    }
}
