<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class ReviewUpdateTest extends ReviewTestCase
{
    // ゲストによるレビュー編集画面へのアクセスを拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_editing_a_review(): void
    {
        $review = Review::factory()->create();

        $this->get(route('reviews.edit', $review))->assertRedirect(route('login'));
    }

    // ゲストによるレビュー更新を拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_updating_a_review(): void
    {
        $review = Review::factory()->create();

        $this->put(route('reviews.update', $review), $this->reviewData())->assertRedirect(route('login'));
    }

    // 所有者がレビューを更新できることを確認する。
    public function test_review_owner_can_update_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            $this->reviewData(['rating' => 3, 'comment' => '更新後のコメントです。'])
        );

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新後のコメントです。',
        ]);
    }

    // 所有者がレビュー編集画面を表示できることを確認する。
    public function test_review_owner_can_view_the_edit_page(): void
    {
        $owner = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('reviews.edit', $review))
            ->assertOk();
    }

    // 所有者以外がレビュー編集画面を表示できないことを確認する。
    public function test_non_owner_cannot_view_the_review_edit_page(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit', $review))
            ->assertForbidden();
    }

    // 所有者以外がレビューを更新できないことを確認する。
    public function test_non_owner_cannot_update_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('reviews.update', $review), $this->reviewData())
            ->assertForbidden();
    }

    // レビュー更新時の評価を検証することを確認する。
    #[DataProviderExternal(ReviewTestCase::class, 'invalidRatingCases')]
    public function test_review_update_validates_rating(mixed $rating): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData(['rating' => $rating]))
            ->assertSessionHasErrors('rating');
    }

    // レビュー更新時のコメントを検証することを確認する。
    public function test_review_update_validates_comment(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください']);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData(['comment' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['comment' => 'コメントが長すぎます']);
    }

    // レビュー更新時にコメントの上限255文字を受理することを確認する。
    public function test_review_update_accepts_comment_at_the_maximum_length(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);
        $comment = str_repeat('更', 255);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData([
                'comment' => $comment,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => $comment,
        ]);
    }
}
