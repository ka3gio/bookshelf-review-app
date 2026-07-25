<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($book->user->is($user));
    }

    public function test_book_can_belong_to_multiple_genres(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->modelKeys());

        $this->assertCount(2, $book->genres);
        $this->assertTrue($book->genres->contains($genres->first()));
        $this->assertTrue($book->genres->contains($genres->last()));
    }

    public function test_book_can_have_multiple_reviews(): void
    {
        $book = Book::factory()->create();
        $reviews = Review::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->reviews);
        $this->assertTrue($book->reviews->contains($reviews->first()));
        $this->assertTrue($book->reviews->contains($reviews->last()));
    }

    public function test_book_can_be_favorited_by_multiple_users(): void
    {
        $book = Book::factory()->create();
        $users = User::factory()->count(2)->create();

        $book->favoritedByUsers()->attach($users->modelKeys());

        $this->assertCount(2, $book->favoritedByUsers);
        $this->assertTrue($book->favoritedByUsers->contains($users->first()));
        $this->assertTrue($book->favoritedByUsers->contains($users->last()));
    }
}
