<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_book_information_relations_and_review_statistics(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book->genres()->attach($genre);
        $reviewer = User::factory()->create();
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 4]);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.genres.0.id', $genre->id)
            ->assertJsonPath('data.0.reviews.0.rating', 5)
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.review_count', 2);
    }

    public function test_show_returns_book_details(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);
        $book->genres()->attach($genre);
        $review = Review::factory()->create(['book_id' => $book->id, 'rating' => 3]);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.genres.0.id', $genre->id)
            ->assertJsonPath('data.reviews.0.id', $review->id)
            ->assertJsonPath('data.average_rating', 3)
            ->assertJsonPath('data.review_count', 1);
    }

    public function test_show_returns_not_found_for_a_nonexistent_book(): void
    {
        $this->getJson('/api/books/999999')->assertNotFound();
    }

    public function test_index_can_filter_books_by_title_author_and_genre(): void
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

        foreach ([
            ['keyword' => 'API設計'],
            ['keyword' => '山田太郎'],
            ['genre_id' => $phpGenre->id],
        ] as $query) {
            $this->getJson('/api/books?' . http_build_query($query))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $targetBook->id);
        }
    }

    public function test_public_api_can_store_a_book_and_its_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $payload = $this->bookPayload($genres->modelKeys(), ['user_id' => $user->id]);

        $this->postJson('/api/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.user.user_id', $user->id);

        $book = Book::where('isbn', $payload['isbn'])->firstOrFail();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'user_id' => $user->id]);
        $this->assertEqualsCanonicalizing($genres->modelKeys(), $book->genres()->pluck('genres.id')->all());
    }

    public function test_store_returns_validation_errors(): void
    {
        $this->postJson('/api/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'title', 'author', 'isbn', 'published_date', 'genres']);
    }

    public function test_public_api_can_update_a_book_and_sync_its_genres(): void
    {
        $book = Book::factory()->create();
        $oldGenre = Genre::factory()->create();
        $newGenres = Genre::factory()->count(2)->create();
        $book->genres()->attach($oldGenre);
        $payload = $this->bookPayload($newGenres->modelKeys(), [
            'title' => '更新後の書籍',
            'isbn' => $book->isbn,
        ]);

        $this->putJson("/api/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', '更新後の書籍');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後の書籍']);
        $this->assertEqualsCanonicalizing($newGenres->modelKeys(), $book->fresh()->genres()->pluck('genres.id')->all());
    }

    public function test_public_api_can_delete_a_book(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
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
