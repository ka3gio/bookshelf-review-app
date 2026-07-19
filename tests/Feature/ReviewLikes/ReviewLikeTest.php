<?php

namespace Tests\Feature\ReviewLikes;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_like_a_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_liking_a_review_again_removes_the_like(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_cannot_create_duplicate_review_like_records(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseCount('review_likes', 1);
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_book_show_displays_like_count_for_each_review(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $review->likedByUsers()->attach(User::factory()->count(2)->create());

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('いいね (2)');
    }

    public function test_guest_is_redirected_to_login_when_liking_a_review(): void
    {
        $review = Review::factory()->create();

        $this->post(route('reviews.like', $review))
            ->assertRedirect(route('login'));
    }
}
