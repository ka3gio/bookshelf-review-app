<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // ユーザーが所有する複数書籍を取得できることを確認する。
    public function test_user_can_own_multiple_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->books);
        $this->assertTrue($user->books->contains($books->first()));
        $this->assertTrue($user->books->contains($books->last()));
    }

    // ユーザーが投稿した複数レビューを取得できることを確認する。
    public function test_user_can_post_multiple_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create()->id,
        ]);

        $this->assertCount(2, $user->reviews);
        $this->assertTrue($user->reviews->contains($reviews->first()));
        $this->assertTrue($user->reviews->contains($reviews->last()));
    }

    // ユーザーのお気に入り書籍を複数取得できることを確認する。
    public function test_user_can_favorite_multiple_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create([]);

        $user->favoriteBooks()->attach($books->modelKeys());

        $this->assertCount(2, $user->favoriteBooks);
        $this->assertTrue($user->favoriteBooks->contains($books->first()));
        $this->assertTrue($user->favoriteBooks->contains($books->last()));
    }

    // ユーザーがいいねした複数レビューを取得できることを確認する。
    public function test_user_can_like_multiple_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create([
            'book_id' => Book::factory()->create()->id,
        ]);

        $user->likedReviews()->attach($reviews->modelKeys());

        $this->assertCount(2, $user->likedReviews);
        $this->assertTrue($user->likedReviews->contains($reviews->first()));
        $this->assertTrue($user->likedReviews->contains($reviews->last()));
    }

    // ユーザーに複数の読書計画を紐付けられることを確認する。
    public function test_user_can_have_multiple_reading_plans(): void
    {
        $user = User::factory()->create();
        $plans = ReadingPlan::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->readingPlans);
        $this->assertTrue($user->readingPlans->contains($plans->first()));
        $this->assertTrue($user->readingPlans->contains($plans->last()));
    }
}
