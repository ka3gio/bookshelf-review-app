<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class BookUpdateTest extends BookTestCase
{
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

    public function test_book_update_validates_input(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        foreach ($this->invalidBookCases('ISBNは13桁の数値で入力する必要があります') as [$overrides, $field, $message, $duplicate]) {
            $book = Book::factory()->create(['user_id' => $owner->id]);

            if ($duplicate ?? false) {
                Book::factory()->create(['isbn' => $overrides['isbn']]);
            }

            $this->actingAs($owner)
                ->put(route('books.update', $book), $this->bookData([$genre->id], $overrides))
                ->assertSessionHasErrors([$field => $message]);
        }
    }

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
}
