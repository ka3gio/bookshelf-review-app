<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_own_multiple_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->books);
        $this->assertTrue($user->books->contains($books->first()));
        $this->assertTrue($user->books->contains($books->last()));
    }

    public function test_user_can_post_multiple_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create()->id,
        ]);

        $this->assertCount(2, $user->reviews);
        $this->assertTrue($user->reviews->contains($reviews->first()));
        $this->assertTrue($user->reviews->contains($reviews->last()));
    }

    public function test_user_can_favorite_multiple_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create([]);

        $user->favorites()->attach($books->modelKeys());

        $this->assertCount(2, $user->favorites);
        $this->assertTrue($user->favorites->contains($books->first()));
        $this->assertTrue($user->favorites->contains($books->last()));
    }

    public function test_user_can_like_multiple_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create([
            'book_id' => Book::factory()->create()->id,
        ]);

        $user->review_likes()->attach($reviews->modelKeys());

        $this->assertCount(2, $user->review_likes);
        $this->assertTrue($user->review_likes->contains($reviews->first()));
        $this->assertTrue($user->review_likes->contains($reviews->last()));
    }
}
