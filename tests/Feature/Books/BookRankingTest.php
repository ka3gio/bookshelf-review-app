<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRankingTest extends TestCase
{
    use RefreshDatabase;

    // ゲストがランキングを表示できることを確認する。
    public function test_guest_can_view_ranking(): void
    {
        $this->get(route('ranking.index'))->assertOk();
    }

    // 認証済みユーザーがランキングを表示できることを確認する。
    public function test_authenticated_user_can_view_ranking(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('ranking.index'))
            ->assertOk();
    }

    // 評価平均順で上位10冊だけをランキング表示することを確認する。
    public function test_ranking_is_sorted_by_average_rating_and_limited_to_ten_books(): void
    {
        $ratings = [5, 5, 5, 4, 4, 4, 3, 3, 3, 2, 1];
        $books = collect($ratings)->map(function (int $rating) {
            $book = Book::factory()->create();
            Review::factory()->create(['book_id' => $book->id, 'rating' => $rating]);

            return $book;
        });

        $response = $this->get(route('ranking.index'));

        $response->assertOk()->assertViewHas('rankedBooks', function ($rankedBooks) use ($books) {
            return $rankedBooks->count() === 10
                && $rankedBooks->pluck('id')->all() === $books->take(10)->pluck('id')->all();
        });
    }

    // レビューのない書籍をランキングから除外することを確認する。
    public function test_books_without_reviews_are_excluded_from_ranking(): void
    {
        $reviewedBook = Book::factory()->create(['title' => 'レビューあり書籍']);
        $unreviewedBook = Book::factory()->create(['title' => 'レビューなし書籍']);
        Review::factory()->create(['book_id' => $reviewedBook->id, 'rating' => 5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('レビューあり書籍')
            ->assertDontSee('レビューなし書籍')
            ->assertViewHas('rankedBooks', function ($rankedBooks) use ($unreviewedBook) {
                return ! $rankedBooks->contains($unreviewedBook);
            });
    }

    // ランキングに書籍情報と評価平均を表示することを確認する。
    public function test_ranking_displays_book_information_and_average_rating(): void
    {
        $book = Book::factory()->create([
            'title' => 'ランキング対象書籍',
            'author' => 'ランキング著者',
            'image_url' => 'https://example.com/book.jpg',
        ]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee([
                'ランキング対象書籍',
                'ランキング著者',
                'https://example.com/book.jpg',
                '5.00',
                '1件のレビュー',
                '平均評価',
            ]);
    }

    // 同評価時にレビュー数とIDで順位を決めることを確認する。
    public function test_ranking_uses_review_count_then_id_to_break_ties(): void
    {
        $moreReviewedBook = Book::factory()->create(['title' => 'レビュー数が多い書籍']);
        $firstCreatedBook = Book::factory()->create(['title' => 'IDが小さい書籍']);
        $lastCreatedBook = Book::factory()->create(['title' => 'IDが大きい書籍']);

        Review::factory()->count(2)->create(['book_id' => $moreReviewedBook->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $firstCreatedBook->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $lastCreatedBook->id, 'rating' => 4]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($rankedBooks) use ($moreReviewedBook, $firstCreatedBook, $lastCreatedBook) {
                return $rankedBooks->modelKeys() === [
                    $moreReviewedBook->id,
                    $firstCreatedBook->id,
                    $lastCreatedBook->id,
                ];
            });
    }
}
