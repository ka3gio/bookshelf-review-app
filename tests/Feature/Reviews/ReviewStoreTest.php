<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class ReviewStoreTest extends ReviewTestCase
{
    // 認証済みユーザーが書籍へレビューを投稿できることを確認する。
    public function test_authenticated_user_can_post_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $reviewData = $this->reviewData();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), $reviewData);

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $reviewData['rating'],
            'comment' => $reviewData['comment'],
        ]);
    }

    // ゲストによるレビュー投稿を拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_posting_a_review(): void
    {
        $book = Book::factory()->create();

        $this->post(route('reviews.store', $book), $this->reviewData())
            ->assertRedirect(route('login'));
    }

    // レビュー登録時の評価を検証することを確認する。
    #[DataProviderExternal(ReviewTestCase::class, 'invalidRatingCases')]
    public function test_review_store_validates_rating(mixed $rating): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData(['rating' => $rating]))
            ->assertSessionHasErrors('rating');
    }

    // レビュー登録時のコメントを検証することを確認する。
    public function test_review_store_validates_comment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください']);

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData(['comment' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['comment' => 'コメントが長すぎます']);
    }

    // レビューコメントの上限255文字を受理することを確認する。
    public function test_review_store_accepts_comment_at_the_maximum_length(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $comment = str_repeat('レ', 255);

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData([
                'comment' => $comment,
            ]))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'comment' => $comment,
        ]);
    }
}
