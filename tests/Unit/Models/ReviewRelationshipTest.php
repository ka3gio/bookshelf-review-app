<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => Book::factory()->create()->id,
        ]);

        $this->assertTrue($review->user->is($user));
    }

    public function test_review_belongs_to_a_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);

        $this->assertTrue($review->book->is($book));
    }

    public function test_review_can_be_liked_by_multiple_users(): void
    {
        $review = Review::factory()->create([
            'book_id' => Book::factory()->create()->id,
        ]);
        $users = User::factory()->count(2)->create();

        $review->likedByUsers()->attach($users->modelKeys());

        $this->assertCount(2, $review->likedByUsers);
        $this->assertTrue($review->likedByUsers->contains($users->first()));
        $this->assertTrue($review->likedByUsers->contains($users->last()));
    }
}
