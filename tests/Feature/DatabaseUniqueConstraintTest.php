<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_names_are_unique(): void
    {
        Genre::factory()->create(['name' => '技術書']);

        $this->expectException(QueryException::class);

        Genre::factory()->create(['name' => '技術書']);
    }

    public function test_favorite_user_and_book_pairs_are_unique(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book);

        $this->expectException(QueryException::class);

        $user->favoriteBooks()->attach($book);
    }

    public function test_book_and_genre_pairs_are_unique(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $this->expectException(QueryException::class);

        $book->genres()->attach($genre);
    }

    public function test_review_and_user_like_pairs_are_unique(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();
        $review->likedByUsers()->attach($user);

        $this->expectException(QueryException::class);

        $review->likedByUsers()->attach($user);
    }
}
