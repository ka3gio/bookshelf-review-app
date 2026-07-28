<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Testing\TestResponse;

class BookIndexQueryTest extends BookTestCase
{
    // タイトルまたは著者の部分一致で書籍を検索できることを確認する。
    public function test_keyword_search_matches_partial_title_or_author(): void
    {
        $titleMatch = Book::factory()->create([
            'title' => 'Laravel実践入門',
            'author' => '山田太郎',
        ]);
        $authorMatch = Book::factory()->create([
            'title' => 'Webアプリケーション設計',
            'author' => 'Laravel研究会',
        ]);
        Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', ['keyword' => 'Laravel']));

        $response->assertStatus(200);
        $this->assertBookIdSet($response, [$authorMatch->id, $titleMatch->id]);
    }

    // 選択したジャンルの書籍だけを表示することを確認する。
    public function test_genre_filter_displays_only_books_in_the_selected_genre(): void
    {
        $selectedGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $matchingBook = Book::factory()->create();
        $otherBook = Book::factory()->create();
        $matchingBook->genres()->attach($selectedGenre);
        $otherBook->genres()->attach($otherGenre);

        $response = $this->get(route('books.index', ['genre' => $selectedGenre->id]));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$matchingBook->id]);
    }

    // キーワードとジャンルの両条件を満たす書籍だけを表示することを確認する。
    public function test_keyword_and_genre_filters_require_both_conditions(): void
    {
        $selectedGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $matchingBook = Book::factory()->create(['title' => 'Laravel設計']);
        $keywordOnlyBook = Book::factory()->create(['title' => 'Laravel入門']);
        $genreOnlyBook = Book::factory()->create(['title' => 'PHP設計']);
        $matchingBook->genres()->attach($selectedGenre);
        $keywordOnlyBook->genres()->attach($otherGenre);
        $genreOnlyBook->genres()->attach($selectedGenre);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $selectedGenre->id,
        ]));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$matchingBook->id]);
    }

    // 検索結果がなくても一覧画面を正常表示することを確認する。
    public function test_no_search_results_still_returns_the_index_page(): void
    {
        $book = Book::factory()->create(['title' => 'PHP入門']);

        $response = $this->get(route('books.index', ['keyword' => '存在しない書籍']));

        $response
            ->assertStatus(200)
            ->assertSee('書籍が見つかりませんでした。')
            ->assertDontSee($book->title);
        $this->assertBookIds($response, []);
    }

    // SQLインジェクション文字列が検索条件として安全に処理されることを確認する。
    public function test_keyword_search_safely_handles_sql_injection_payloads(): void
    {
        Book::factory()->create(['title' => '安全な書籍A']);
        Book::factory()->create(['title' => '安全な書籍B']);

        $response = $this->get(route('books.index', [
            'keyword' => "%' OR 1=1 --",
        ]));

        $response->assertOk();
        $this->assertBookIds($response, []);
        $this->assertDatabaseCount('books', 2);
    }

    // Web検索語の上限255文字を受理することを確認する。
    public function test_keyword_search_accepts_the_maximum_length(): void
    {
        $this->get(route('books.index', [
            'keyword' => str_repeat('a', 255),
        ]))->assertOk();
    }

    // ページ移動後もキーワード検索条件が維持されることを確認する。
    public function test_keyword_filter_is_preserved_when_paginating(): void
    {
        Book::factory()->count(11)->create(['author' => '検索対象著者']);
        $excludedBook = Book::factory()->create(['author' => '対象外著者']);

        $firstPage = $this->get(route('books.index', ['keyword' => '検索対象']));

        $firstPage->assertStatus(200);
        $firstPage->assertViewHas('books', function ($books) {
            $query = $this->queryParameters($books->url(2));

            return $books->total() === 11
                && $query['keyword'] === '検索対象'
                && $query['page'] === '2';
        });

        $secondPage = $this->get(route('books.index', [
            'keyword' => '検索対象',
            'page' => 2,
        ]));

        $secondPage->assertStatus(200);
        $secondPage->assertViewHas('books', function ($books) use ($excludedBook) {
            return $books->currentPage() === 2
                && $books->count() === 1
                && ! $books->getCollection()->contains('id', $excludedBook->id);
        });
    }

    // 新着順で登録日時の降順に並ぶことを確認する。
    public function test_latest_sort_orders_books_by_created_at_descending(): void
    {
        $oldest = Book::factory()->create(['created_at' => '2024-01-01 00:00:00']);
        $middle = Book::factory()->create(['created_at' => '2024-02-01 00:00:00']);
        $latest = Book::factory()->create(['created_at' => '2024-03-01 00:00:00']);

        $response = $this->get(route('books.index', ['sort' => 'latest']));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$latest->id, $middle->id, $oldest->id]);
    }

    // 古い順で登録日時の昇順に並ぶことを確認する。
    public function test_oldest_sort_orders_books_by_created_at_ascending(): void
    {
        $middle = Book::factory()->create(['created_at' => '2024-02-01 00:00:00']);
        $latest = Book::factory()->create(['created_at' => '2024-03-01 00:00:00']);
        $oldest = Book::factory()->create(['created_at' => '2024-01-01 00:00:00']);

        $response = $this->get(route('books.index', ['sort' => 'oldest']));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$oldest->id, $middle->id, $latest->id]);
    }

    // タイトルの昇順に書籍が並ぶことを確認する。
    public function test_title_sort_orders_books_by_title_ascending(): void
    {
        $charlie = Book::factory()->create(['title' => 'Charlie']);
        $alpha = Book::factory()->create(['title' => 'Alpha']);
        $bravo = Book::factory()->create(['title' => 'Bravo']);

        $response = $this->get(route('books.index', ['sort' => 'title']));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$alpha->id, $bravo->id, $charlie->id]);
    }

    // 評価平均とレビュー数で並べ未評価書籍を末尾にすることを確認する。
    public function test_rating_sort_orders_by_average_then_review_count_and_puts_unreviewed_books_last(): void
    {
        $highestRated = Book::factory()->create();
        $moreReviewed = Book::factory()->create();
        $lessReviewed = Book::factory()->create();
        $unreviewed = Book::factory()->create();

        Review::factory()->create(['book_id' => $highestRated->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $moreReviewed->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $moreReviewed->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $moreReviewed->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $lessReviewed->id, 'rating' => 4]);

        $response = $this->get(route('books.index', ['sort' => 'rating']));

        $response->assertStatus(200);
        $this->assertBookIds($response, [
            $highestRated->id,
            $moreReviewed->id,
            $lessReviewed->id,
            $unreviewed->id,
        ]);
    }

    // 検索、ジャンル絞り込み、並び替えを併用できることを確認する。
    public function test_filters_and_sort_can_be_combined(): void
    {
        $selectedGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $bravo = Book::factory()->create(['title' => 'Laravel Bravo']);
        $alpha = Book::factory()->create(['title' => 'Laravel Alpha']);
        $wrongKeyword = Book::factory()->create(['title' => 'PHP Alpha']);
        $wrongGenre = Book::factory()->create(['title' => 'Laravel Charlie']);
        $bravo->genres()->attach($selectedGenre);
        $alpha->genres()->attach($selectedGenre);
        $wrongKeyword->genres()->attach($selectedGenre);
        $wrongGenre->genres()->attach($otherGenre);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
            'genre' => $selectedGenre->id,
            'sort' => 'title',
        ]));

        $response->assertStatus(200);
        $this->assertBookIds($response, [$alpha->id, $bravo->id]);
    }

    // ページ移動後も並び替え条件が維持されることを確認する。
    public function test_sort_is_preserved_when_paginating(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get(route('books.index', ['sort' => 'oldest']));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            $query = $this->queryParameters($books->url(2));

            return $query['sort'] === 'oldest'
                && $query['page'] === '2';
        });
    }

    // 不正な検索条件をバリデーションで拒否することを確認する。
    public function test_invalid_query_parameters_return_validation_errors(): void
    {
        $this->get(route('books.index', ['keyword' => str_repeat('a', 256)]))
            ->assertStatus(302)
            ->assertSessionHasErrors('keyword');

        $this->get(route('books.index', ['genre' => 999999]))
            ->assertStatus(302)
            ->assertSessionHasErrors('genre');

        $this->get(route('books.index', ['sort' => 'invalid']))
            ->assertStatus(302)
            ->assertSessionHasErrors('sort');
    }

    private function assertBookIds(TestResponse $response, array $expectedIds): void
    {
        $response->assertViewHas('books', function ($books) use ($expectedIds) {
            return $books->getCollection()->modelKeys() === $expectedIds;
        });
    }

    private function assertBookIdSet(TestResponse $response, array $expectedIds): void
    {
        $response->assertViewHas('books', function ($books) use ($expectedIds) {
            $actualIds = $books->getCollection()->modelKeys();
            sort($actualIds);
            sort($expectedIds);

            return $actualIds === $expectedIds;
        });
    }

    private function queryParameters(string $url): array
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }
}
