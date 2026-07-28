<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    // 書籍一覧APIが関連情報とレビュー統計を返し、レビュー本体は返さないことを確認する。
    public function test_index_returns_book_information_relations_and_review_statistics(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book->genres()->attach($genre);
        $reviewer = User::factory()->create();
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 4]);

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [$this->bookResponseStructure([
                    'average_rating',
                    'review_count',
                ])],
            ])
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.genres.0.id', $genre->id)
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.review_count', 2)
            ->assertJsonMissingPath('data.0.reviews');
    }

    // 書籍詳細APIが書籍と関連情報を返すことを確認する。
    public function test_show_returns_book_details(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);
        $book->genres()->attach($genre);
        $review = Review::factory()->create(['book_id' => $book->id, 'rating' => 3]);
        $review->likedByUsers()->attach(User::factory()->create());

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => $this->bookResponseStructure([
                    'average_rating',
                    'review_count',
                    'reviews' => [[
                        'id',
                        'user' => ['user_id', 'user_name'],
                        'rating',
                        'comment',
                        'created_at',
                        'updated_at',
                        'likes_count',
                    ]],
                ]),
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.genres.0.id', $genre->id)
            ->assertJsonPath('data.reviews.0.id', $review->id)
            ->assertJsonPath('data.reviews.0.user.user_id', $review->user_id)
            ->assertJsonPath('data.reviews.0.likes_count', 1)
            ->assertJsonPath('data.average_rating', 3)
            ->assertJsonPath('data.review_count', 1)
            ->assertJsonMissingPath('data.created_at')
            ->assertJsonMissingPath('data.updated_at')
            ->assertJsonMissingPath('data.genres.0.created_at')
            ->assertJsonMissingPath('data.genres.0.updated_at')
            ->assertJsonMissingPath('data.reviews.0.book_id')
            ->assertJsonMissingPath('data.reviews.0.liked_count');
    }

    // 存在しない書籍に対する各APIが共通形式の404を返すことを確認する。
    #[DataProvider('bookNotFoundCases')]
    public function test_book_endpoints_return_not_found(
        string $method,
        bool $requiresAuthentication
    ): void {
        if ($requiresAuthentication) {
            Sanctum::actingAs(User::factory()->create());
        }

        $payload = $method === 'PUT'
            ? $this->bookPayload([Genre::factory()->create()->id])
            : [];

        $this->json($method, '/api/v1/books/999999', $payload)
            ->assertNotFound()
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    // 書籍一覧APIをタイトル、著者、ジャンルで絞り込めることを確認する。
    #[DataProvider('bookFilterCases')]
    public function test_index_can_filter_books_by_title_author_and_genre(string $filter): void
    {
        $phpGenre = Genre::factory()->create(['name' => 'PHP']);
        $novelGenre = Genre::factory()->create(['name' => '小説']);
        $targetBook = Book::factory()->create([
            'title' => 'Laravel API設計',
            'author' => '山田太郎',
            'isbn' => '9784000000001',
        ]);
        $targetBook->genres()->attach($phpGenre);
        $otherBook = Book::factory()->create([
            'title' => '小説の書き方',
            'author' => '佐藤花子',
            'isbn' => '9784000000002',
        ]);
        $otherBook->genres()->attach($novelGenre);

        $query = match ($filter) {
            'title' => ['keyword' => 'API設計'],
            'author' => ['keyword' => '山田太郎'],
            'genre' => ['genre_id' => $phpGenre->id],
        };

        $this->getJson('/api/v1/books?'.http_build_query($query))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetBook->id);
    }

    // SQLインジェクション文字列が検索条件として安全に処理されることを確認する。
    public function test_index_safely_handles_sql_injection_payloads(): void
    {
        Book::factory()->create(['title' => '安全な書籍A']);
        Book::factory()->create(['title' => '安全な書籍B']);

        $this->getJson('/api/v1/books?'.http_build_query([
            'keyword' => "%' OR 1=1 --",
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('books', 2);
    }

    // API検索語の上限100文字を受理し101文字を拒否することを確認する。
    public function test_index_validates_keyword_length_boundaries(): void
    {
        $this->getJson('/api/v1/books?'.http_build_query([
            'keyword' => str_repeat('a', 100),
        ]))->assertOk();

        $this->getJson('/api/v1/books?'.http_build_query([
            'keyword' => str_repeat('a', 101),
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('error', '入力内容に誤りがあります')
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.keyword.0', 'キーワードは100文字以内で入力して下さい')
            ->assertJsonValidationErrors('keyword');
    }

    // APIの1ページ当たり件数が1から100の境界内だけ許可されることを確認する。
    public function test_index_validates_per_page_boundaries(): void
    {
        $this->getJson('/api/v1/books?per_page=1')->assertOk();
        $this->getJson('/api/v1/books?per_page=100')->assertOk();
        $this->getJson('/api/v1/books?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/books?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    // APIページネーションが指定ページのデータと正しいメタ情報・リンクを返すことを確認する。
    public function test_index_paginates_books_and_returns_pagination_metadata(): void
    {
        $books = Book::factory()->count(5)->create();

        $this->getJson('/api/v1/books?page=2&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $books[2]->id)
            ->assertJsonPath('data.1.id', $books[3]->id)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.from', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.to', 4)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('links.prev', 'http://localhost/api/v1/books?page=1')
            ->assertJsonPath('links.next', 'http://localhost/api/v1/books?page=3');
    }

    // 認証済みユーザーがAPIからジャンル付き書籍を登録できることを確認する。
    public function test_authenticated_user_can_store_a_book_and_its_genres(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genres = Genre::factory()->count(2)->create();
        $payload = $this->bookPayload($genres->modelKeys());

        $this->postJson('/api/v1/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.user.user_id', $user->id)
            ->assertJsonCount(2, 'data.genres');

        $book = Book::where('isbn', $payload['isbn'])->firstOrFail();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'user_id' => $user->id]);
        $this->assertEqualsCanonicalizing($genres->modelKeys(), $book->genres()->pluck('genres.id')->all());
    }

    // API書籍登録時の必須項目を検証することを確認する。
    public function test_store_returns_validation_errors(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', [])
            ->assertUnprocessable()
            ->assertJsonPath('error', '入力内容に誤りがあります')
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.title.0', 'タイトルを入力してください')
            ->assertJsonPath('errors.author.0', '著者名を入力してください')
            ->assertJsonPath('errors.genres.0', 'ジャンルを入力してください')
            ->assertJsonValidationErrors(['title', 'author', 'genres']);
    }

    // API書籍登録で同じジャンルIDを重複指定できないことを確認する。
    public function test_store_rejects_duplicate_genre_ids_without_creating_a_book(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $genre = Genre::factory()->create();
        $bookCount = Book::count();

        $this->postJson('/api/v1/books', $this->bookPayload([
            $genre->id,
            $genre->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonFragment([
                'genres.0' => [
                    '同じジャンルを重複して指定できません',
                ],
            ]);

        $this->assertDatabaseCount('books', $bookCount);
    }

    // API書籍登録が各文字数上限ちょうどの入力を受理することを確認する。
    public function test_store_accepts_values_at_the_maximum_length_boundaries(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $genre = Genre::factory()->create();
        $payload = $this->bookPayload([$genre->id], [
            'title' => str_repeat('t', 255),
            'author' => str_repeat('a', 100),
            'isbn' => null,
            'description' => str_repeat('d', 1000),
            'image_url' => str_pad('https://example.com/', 255, 'i'),
        ]);

        $this->postJson('/api/v1/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', $payload['title']);
    }

    // 所有者がAPIから書籍とジャンルを更新できることを確認する。
    public function test_book_owner_can_update_a_book_and_sync_its_genres(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs($book->user);
        $oldGenre = Genre::factory()->create();
        $newGenres = Genre::factory()->count(2)->create();
        $book->genres()->attach($oldGenre);
        $payload = $this->bookPayload($newGenres->modelKeys(), [
            'title' => '更新後の書籍',
            'isbn' => $book->isbn,
        ]);

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', '更新後の書籍')
            ->assertJsonCount(2, 'data.genres');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後の書籍']);
        $this->assertEqualsCanonicalizing($newGenres->modelKeys(), $book->fresh()->genres()->pluck('genres.id')->all());
    }

    // 所有者がAPIから書籍を削除できることを確認する。
    public function test_book_owner_can_delete_a_book(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs($book->user);

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    // 所有者以外がAPIから書籍を更新できないことを確認する。
    public function test_non_owner_cannot_update_a_book_through_the_api(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $genre = Genre::factory()->create();

        $this->putJson("/api/v1/books/{$book->id}", $this->bookPayload([$genre->id], [
            'isbn' => $book->isbn,
        ]))
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません',
                'error_code' => 'FORBIDDEN',
            ]);
    }

    // 所有者以外がAPIから書籍を削除できないことを確認する。
    public function test_non_owner_cannot_delete_a_book_through_the_api(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません',
                'error_code' => 'FORBIDDEN',
            ]);

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    // API書籍更新時に必須項目のバリデーションエラーを返すことを確認する。
    public function test_update_returns_validation_errors(): void
    {
        $book = Book::factory()->create();
        Sanctum::actingAs($book->user);

        $this->putJson("/api/v1/books/{$book->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'author', 'genres']);
    }

    // API書籍更新で同じジャンルIDを重複指定した場合に既存データを変更しないことを確認する。
    public function test_update_rejects_duplicate_genre_ids_without_changing_the_book(): void
    {
        $book = Book::factory()->create(['title' => '更新前のタイトル']);
        $currentGenre = Genre::factory()->create();
        $duplicateGenre = Genre::factory()->create();
        $book->genres()->attach($currentGenre);
        Sanctum::actingAs($book->user);

        $this->putJson(
            "/api/v1/books/{$book->id}",
            $this->bookPayload([
                $duplicateGenre->id,
                $duplicateGenre->id,
            ], [
                'title' => '更新されてはいけないタイトル',
                'isbn' => $book->isbn,
            ])
        )
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonFragment([
                'genres.0' => [
                    '同じジャンルを重複して指定できません',
                ],
            ]);

        $this->assertSame('更新前のタイトル', $book->fresh()->title);
        $this->assertEquals(
            [$currentGenre->id],
            $book->genres()->pluck('genres.id')->all()
        );
    }

    // API書籍更新が各文字数上限ちょうどの入力を受理することを確認する。
    public function test_update_accepts_values_at_the_maximum_length_boundaries(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        Sanctum::actingAs($book->user);
        $payload = $this->bookPayload([$genre->id], [
            'title' => str_repeat('t', 255),
            'author' => str_repeat('a', 100),
            'isbn' => $book->isbn,
            'description' => str_repeat('d', 1000),
            'image_url' => str_pad('https://example.com/', 255, 'i'),
        ]);

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', $payload['title']);
    }

    public static function bookFilterCases(): array
    {
        return [
            'title' => ['title'],
            'author' => ['author'],
            'genre' => ['genre'],
        ];
    }

    public static function bookNotFoundCases(): array
    {
        return [
            'show' => ['GET', false],
            'update' => ['PUT', true],
            'destroy' => ['DELETE', true],
        ];
    }

    private function bookResponseStructure(array $additionalFields = []): array
    {
        return array_merge([
            'id',
            'user' => ['user_id', 'user_name'],
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
            'genres' => [[
                'id',
                'name',
            ]],
        ], $additionalFields);
    }

    private function bookPayload(array $genreIds, array $overrides = []): array
    {
        return array_replace([
            'title' => 'APIテスト書籍',
            'author' => 'APIテスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => 'APIテスト用の書籍説明です。',
            'image_url' => 'https://example.com/api-book.jpg',
            'genres' => $genreIds,
        ], $overrides);
    }
}
