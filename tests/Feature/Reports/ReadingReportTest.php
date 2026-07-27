<?php

namespace Tests\Feature\Reports;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingReportTest extends TestCase
{
    use RefreshDatabase;

    // ゲストによる読書レポートへのアクセスを拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_viewing_report(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    // 認証済みユーザーが読書レポートを表示できることを確認する。
    public function test_authenticated_user_can_view_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertSee('マイ読書レポート');
    }

    // ログイン中ユーザーのデータだけで概要を集計することを確認する。
    public function test_summary_is_aggregated_from_only_the_authenticated_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach ([5, 4, 3] as $rating) {
            $this->createReview($user, $rating);
        }
        $this->createReview($otherUser, 1);

        $completedBook = Book::factory()->create();
        $inProgressBook = Book::factory()->create();

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'target_date' => '2026-08-01',
            'status' => 3,
        ]);
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'target_date' => '2026-08-02',
            'status' => 3,
        ]);
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'target_date' => '2026-08-03',
            'status' => 2,
        ]);
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $inProgressBook->id,
            'target_date' => '2026-08-04',
            'status' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['summary']['total_reviews'] === 3
                    && $stats['summary']['books_read'] === 1
                    && (float) $stats['summary']['average_rating'] === 4.0;
            });
    }

    // 自分のレビューだけで星1から5の評価分布を作成することを確認する。
    public function test_rating_distribution_contains_all_stars_and_excludes_other_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach ([1, 2, 2, 3, 3, 3, 5] as $rating) {
            $this->createReview($user, $rating);
        }
        foreach ([1, 4, 4, 4, 5] as $rating) {
            $this->createReview($otherUser, $rating);
        }

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['rating_distribution']
                    ->map(fn ($count) => (int) $count)
                    ->all() === [
                        1 => 1,
                        2 => 2,
                        3 => 3,
                        4 => 0,
                        5 => 1,
                    ];
            });
    }

    // 自分の高評価書籍を評価順で最大5冊表示することを確認する。
    public function test_top_rated_books_are_sorted_by_rating_limited_to_five_and_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $userReviews = collect();

        foreach ([4, 5, 4, 5, 3, 5, 4] as $index => $rating) {
            $userReviews->push($this->createReview($user, $rating, [
                'title' => "本人の書籍{$index}",
            ]));
        }
        $otherReview = $this->createReview($otherUser, 5, [
            'title' => '他ユーザーの高評価書籍',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($userReviews, $otherReview) {
                $topRatedBooks = $stats['top_rated_books'];
                $userBookIds = $userReviews->pluck('book_id');

                return $topRatedBooks->count() === 5
                    && $topRatedBooks->pluck('rating')->all() === [5, 5, 5, 4, 4]
                    && $topRatedBooks->every(
                        fn (array $book) => $userBookIds->contains($book['id'])
                            && $book['rating'] >= 4
                    )
                    && ! $topRatedBooks->pluck('id')->contains($otherReview->book_id);
            })
            ->assertDontSee('他ユーザーの高評価書籍');
    }

    // 自分のレビューからジャンル別評価上位5件を集計することを確認する。
    public function test_genre_rating_trends_are_aggregated_sorted_limited_and_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genreRatings = [
            '文学' => [5, 5],
            '歴史' => [5, 4],
            '科学' => [4, 4],
            '芸術' => [4, 3],
            '料理' => [3, 3],
            '旅行' => [2, 2],
        ];
        $genres = collect();

        foreach ($genreRatings as $name => $ratings) {
            $genre = Genre::factory()->create(['name' => $name]);
            $genres->put($name, $genre);

            foreach ($ratings as $rating) {
                $this->createReviewForGenre($user, $genre, $rating);
            }
        }

        $this->createReviewForGenre($otherUser, $genres['文学'], 1);
        $otherUsersGenre = Genre::factory()->create(['name' => '他ユーザー専用']);
        $this->createReviewForGenre($otherUser, $otherUsersGenre, 5);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($genres) {
                $genreRatings = $stats['genre_ratings'];
                $expectedNames = ['文学', '歴史', '科学', '芸術', '料理'];
                $expectedAverages = [5.0, 4.5, 4.0, 3.5, 3.0];

                return $genreRatings->count() === 5
                    && $genreRatings->pluck('id')->all() === $genres
                        ->only($expectedNames)
                        ->pluck('id')
                        ->all()
                    && $genreRatings->pluck('name')->all() === $expectedNames
                    && $genreRatings->map(
                        fn ($genre) => (float) $genre->average_rating
                    )->all() === $expectedAverages
                    && $genreRatings->every(fn ($genre) => (int) $genre->count === 2);
            })
            ->assertDontSee('旅行')
            ->assertDontSee('他ユーザー専用');
    }

    // レビューのないユーザーに空状態を表示することを確認する。
    public function test_user_without_reviews_can_view_the_empty_state(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.index'));

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) {
                return $stats['summary'] === [
                    'total_reviews' => 0,
                    'books_read' => 0,
                    'average_rating' => null,
                ]
                    && $stats['rating_distribution']->all() === [
                        1 => 0,
                        2 => 0,
                        3 => 0,
                        4 => 0,
                        5 => 0,
                    ]
                    && $stats['top_rated_books']->isEmpty()
                    && $stats['genre_ratings']->isEmpty();
            })
            ->assertSee('4星以上の書籍がありません')
            ->assertSee('ジャンルが設定された書籍のレビューがありません');

        $this->assertSame(5, substr_count($response->getContent(), '0件'));
    }

    private function createReview(User $user, int $rating, array $bookAttributes = []): Review
    {
        $book = Book::factory()->create($bookAttributes);

        return Review::factory()->for($user)->for($book)->create([
            'rating' => $rating,
        ]);
    }

    private function createReviewForGenre(User $user, Genre $genre, int $rating): Review
    {
        $review = $this->createReview($user, $rating);
        $review->book->genres()->attach($genre);

        return $review;
    }
}
