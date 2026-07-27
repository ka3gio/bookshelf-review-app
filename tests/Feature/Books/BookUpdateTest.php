<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProviderExternal;

class BookUpdateTest extends BookTestCase
{
    // 所有者が書籍情報を更新しジャンルを同期できることを確認する。
    public function test_book_owner_can_update_book_and_sync_genres(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $oldGenre = Genre::factory()->create();
        $newGenres = Genre::factory()->count(2)->create();
        $book->genres()->attach($oldGenre);
        $bookData = $this->bookData($newGenres->modelKeys(), [
            'title' => '更新後のタイトル',
            'isbn' => $book->isbn,
        ]);

        $response = $this->actingAs($owner)->put(route('books.update', $book), $bookData);

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後のタイトル']);
        $this->assertEqualsCanonicalizing($newGenres->modelKeys(), $book->genres()->pluck('genres.id')->all());
    }

    // 書籍更新時の各入力規則を検証することを確認する。
    #[DataProviderExternal(BookTestCase::class, 'invalidBookCases')]
    public function test_book_update_validates_input(
        array $overrides,
        string $field,
        string $message,
        bool $duplicate
    ): void {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        if ($duplicate) {
            Book::factory()->create(['isbn' => $overrides['isbn']]);
        }

        $this->actingAs($owner)
            ->put(route('books.update', $book), $this->bookData([$genre->id], $overrides))
            ->assertSessionHasErrors([$field => $message]);
    }

    // 更新対象自身のISBNが重複扱いされないことを確認する。
    public function test_book_update_allows_its_current_isbn(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            $this->bookData([$genre->id], ['isbn' => $book->isbn])
        );

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionDoesntHaveErrors();
    }

    // 書籍更新時に任意項目をnullへ変更できることを確認する。
    public function test_book_can_be_updated_with_nullable_fields_set_to_null(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => '更新前の説明',
            'image_url' => 'https://example.com/before.jpg',
        ]);
        $bookData = $this->bookData([$genre->id], [
            'title' => '任意項目を空にした書籍',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->actingAs($owner)
            ->put(route('books.update', $book), $bookData);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '任意項目を空にした書籍',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);
    }

    // 書籍更新が各文字数上限ちょうどの入力を受理することを確認する。
    public function test_book_can_be_updated_at_the_maximum_length_boundaries(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $payload = $this->bookData([$genre->id], [
            'title' => str_repeat('t', 255),
            'author' => str_repeat('a', 100),
            'isbn' => $book->isbn,
            'description' => str_repeat('d', 1000),
            'image_url' => str_pad('https://example.com/', 255, 'i'),
        ]);

        $this->actingAs($owner)
            ->put(route('books.update', $book), $payload)
            ->assertRedirect(route('books.show', $book))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $payload['title'],
            'author' => $payload['author'],
            'description' => $payload['description'],
            'image_url' => $payload['image_url'],
        ]);
    }
}
