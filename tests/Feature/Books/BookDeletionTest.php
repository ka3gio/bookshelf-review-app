<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;

class BookDeletionTest extends BookTestCase
{
    // 書籍削除時に関連レコードも削除されることを確認する。
    public function test_book_owner_can_delete_book_and_its_related_records(): void
    {
        $owner = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $review = Review::factory()->create(['book_id' => $book->id]);
        $readingPlan = ReadingPlan::create([
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
            'target_date' => '2026-08-01',
            'status' => 1,
        ]);
        $book->genres()->attach($genre);
        $favoriteUser->favoriteBooks()->attach($book);
        $review->likedByUsers()->attach($favoriteUser);

        $response = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertDatabaseMissing('reading_plans', ['id' => $readingPlan->id]);
    }
}
