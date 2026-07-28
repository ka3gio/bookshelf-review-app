<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // 書籍から所有ユーザーを取得できることを確認する。
    public function test_book_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($book->user->is($user));
    }

    // 出版日が日付オブジェクトへキャストされることを確認する。
    public function test_published_date_is_cast_to_a_date_object(): void
    {
        $book = Book::factory()->create(['published_date' => '2024-01-01']);

        $this->assertInstanceOf(CarbonInterface::class, $book->published_date);
        $this->assertSame('2024-01-01', $book->published_date->format('Y-m-d'));
    }

    // 書籍に複数ジャンルを紐付けられることを確認する。
    public function test_book_can_belong_to_multiple_genres(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->modelKeys());

        $this->assertCount(2, $book->genres);
        $this->assertTrue($book->genres->contains($genres->first()));
        $this->assertTrue($book->genres->contains($genres->last()));
    }

    // 書籍に複数レビューを紐付けられることを確認する。
    public function test_book_can_have_multiple_reviews(): void
    {
        $book = Book::factory()->create();
        $reviews = Review::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->reviews);
        $this->assertTrue($book->reviews->contains($reviews->first()));
        $this->assertTrue($book->reviews->contains($reviews->last()));
    }

    // 書籍を複数ユーザーがお気に入りにできることを確認する。
    public function test_book_can_be_favorited_by_multiple_users(): void
    {
        $book = Book::factory()->create();
        $users = User::factory()->count(2)->create();

        $book->favoritedByUsers()->attach($users->modelKeys());

        $this->assertCount(2, $book->favoritedByUsers);
        $this->assertTrue($book->favoritedByUsers->contains($users->first()));
        $this->assertTrue($book->favoritedByUsers->contains($users->last()));
    }

    // 書籍に複数の読書計画を紐付けられることを確認する。
    public function test_book_can_have_multiple_reading_plans(): void
    {
        $book = Book::factory()->create();
        $plans = ReadingPlan::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->readingPlans);
        $this->assertTrue($book->readingPlans->contains($plans->first()));
        $this->assertTrue($book->readingPlans->contains($plans->last()));
    }
}
