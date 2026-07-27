<?php

namespace Tests\Feature\Books;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class GoogleBooksIsbnSearchTest extends BookTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    // 登録画面にISBN検索と自動入力項目が存在することを確認する。
    public function test_create_page_contains_isbn_search_and_autofill_fields(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('books.create'));

        $response
            ->assertStatus(200)
            ->assertSee('id="isbn-search"', false)
            ->assertSee('ISBNは13桁の数字で入力してください。')
            ->assertSee('id="title"', false)
            ->assertSee('id="author"', false)
            ->assertSee('id="published_date"', false)
            ->assertSee('id="description"', false)
            ->assertSee('id="image_url"', false);
    }

    // 有効なISBNからGoogle Booksの書籍情報を取得できることを確認する。
    public function test_valid_isbn_returns_book_data_from_google_books(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['著者一郎', '著者花子'],
                            'publishedDate' => '2024-05-01',
                            'description' => 'Google Booksから取得した説明です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/thumbnail.jpg',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/9784101010014');

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'title' => 'テスト書籍',
                'author' => '著者一郎、著者花子',
                'published_date' => '2024-05-01',
                'description' => 'Google Booksから取得した説明です。',
                'image_url' => 'https://example.com/thumbnail.jpg',
            ]);

        Http::assertSent(function (Request $request) {
            return str_starts_with(
                $request->url(),
                'https://www.googleapis.com/books/v1/volumes?'
            )
                && $request['q'] === 'isbn:9784101010014'
                && $request['maxResults'] === 1;
        });
    }

    // 桁数不正のISBNを外部通信前に拒否することを確認する。
    public function test_invalid_length_isbn_returns_validation_error_without_api_request(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/978410101001')
            ->assertStatus(422)
            ->assertJsonValidationErrors('isbn')
            ->assertJsonPath('errors.isbn.0', 'ISBNは13桁の数字で入力してください。');

        Http::assertNothingSent();
    }

    // 数字以外を含むISBNを外部通信前に拒否することを確認する。
    public function test_non_numeric_isbn_returns_validation_error_without_api_request(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/ABCDEFGHIJKLM')
            ->assertStatus(422)
            ->assertJsonValidationErrors('isbn')
            ->assertJsonPath('errors.isbn.0', 'ISBNは13桁の数字で入力してください。');

        Http::assertNothingSent();
    }

    // ISBN未指定を外部通信前に拒否することを確認する。
    public function test_missing_isbn_returns_validation_error_without_api_request(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn')
            ->assertStatus(422)
            ->assertJsonValidationErrors('isbn')
            ->assertJsonPath('errors.isbn.0', 'ISBNを入力してください。');

        Http::assertNothingSent();
    }

    // 書籍が見つからない場合に利用者向けエラーを返すことを確認する。
    public function test_book_not_found_returns_user_readable_error(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/9784101010014')
            ->assertStatus(404)
            ->assertJsonPath('error', '書籍が見つかりませんでした。');
    }

    // 外部API障害時に内部例外を公開しないことを確認する。
    public function test_google_books_server_error_is_handled_without_exposing_exception(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/9784101010014')
            ->assertStatus(502)
            ->assertJsonPath('error', '書籍情報の取得に失敗しました。')
            ->assertJsonMissingPath('exception');
    }

    // 外部APIタイムアウト時に内部例外を公開しないことを確認する。
    public function test_google_books_timeout_is_handled_without_exposing_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/9784101010014')
            ->assertStatus(503)
            ->assertJsonPath('error', '通信エラーが発生しました。')
            ->assertJsonMissingPath('exception');
    }

    // 不正な外部レスポンスを外部サービス障害として処理することを確認する。
    public function test_invalid_google_books_response_is_handled_as_upstream_failure(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response(
                'invalid-json',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/books/isbn/9784101010014')
            ->assertStatus(502)
            ->assertJsonPath('error', '書籍情報の取得に失敗しました。')
            ->assertJsonMissingPath('exception');
    }

    // ゲストがISBN検索を利用できないことを確認する。
    public function test_guest_cannot_use_isbn_search(): void
    {
        Http::fake();

        $this->getJson('/books/isbn/9784101010014')
            ->assertStatus(401);

        Http::assertNothingSent();
    }
}
