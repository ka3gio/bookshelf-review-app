<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class BookStoreTest extends BookTestCase
{
    // ゲストによる書籍登録画面へのアクセスを拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_viewing_book_create_page(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));
    }

    // ゲストによる書籍登録を拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_storing_a_book(): void
    {
        $this->post(route('books.store'), $this->bookData([]))
            ->assertRedirect(route('login'));
    }

    // 認証済みユーザーが書籍登録画面を表示できることを確認する。
    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('books.create'))
            ->assertOk();
    }

    // 認証済みユーザーがジャンル付き書籍を登録できることを確認する。
    public function test_authenticated_user_can_create_a_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $bookData = $this->bookData($genres->modelKeys());

        $response = $this->actingAs($user)->post(route('books.store'), $bookData);
        $book = Book::where('isbn', $bookData['isbn'])->firstOrFail();

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $bookData['title'],
        ]);
        $this->assertEqualsCanonicalizing($genres->modelKeys(), $book->genres()->pluck('genres.id')->all());
    }

    // 任意項目をnullとして書籍を登録できることを確認する。
    public function test_book_can_be_created_with_nullable_fields_set_to_null(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $bookData = $this->bookData([$genre->id], [
            'title' => '任意項目なしの書籍',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $bookData);

        $book = Book::where('title', '任意項目なしの書籍')->firstOrFail();

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);
    }

    // 書籍登録が各文字数上限ちょうどの入力を受理することを確認する。
    public function test_book_can_be_created_at_the_maximum_length_boundaries(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $payload = $this->bookData([$genre->id], [
            'title' => str_repeat('t', 255),
            'author' => str_repeat('a', 100),
            'isbn' => null,
            'description' => str_repeat('d', 1000),
            'image_url' => str_pad('https://example.com/', 255, 'i'),
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $payload);
        $book = Book::where('title', $payload['title'])->firstOrFail();

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => $payload['title'],
            'author' => $payload['author'],
            'description' => $payload['description'],
            'image_url' => $payload['image_url'],
        ]);
    }

    // 書籍登録時の各入力規則を検証することを確認する。
    #[DataProviderExternal(BookTestCase::class, 'invalidBookCases')]
    public function test_book_store_validates_input(
        array $overrides,
        string $field,
        string $message,
        bool $duplicate
    ): void {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        if ($duplicate) {
            Book::factory()->create(['isbn' => $overrides['isbn']]);
        }

        $this->actingAs($user)
            ->post(route('books.store'), $this->bookData([$genre->id], $overrides))
            ->assertSessionHasErrors([$field => $message]);
    }

    // 書籍登録で重複ジャンルIDと存在しないジャンルIDを拒否することを確認する。
    public function test_book_store_validates_genre_ids(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), $this->bookData([
                $genre->id,
                $genre->id,
            ]))
            ->assertSessionHasErrors([
                'genres.0' => '同じジャンルを重複して指定できません',
            ]);

        $this->actingAs($user)
            ->post(route('books.store'), $this->bookData([PHP_INT_MAX]))
            ->assertSessionHasErrors([
                'genres.0' => '指定されたジャンルは存在しません',
            ]);
    }
}
