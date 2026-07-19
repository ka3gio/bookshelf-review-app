<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;

class ReviewUpdateTest extends ReviewTestCase
{
    public function test_guest_is_redirected_to_login_when_editing_or_updating_a_review(): void
    {
        $review = Review::factory()->create();

        $this->get(route('reviews.edit', $review))->assertRedirect(route('login'));
        $this->put(route('reviews.update', $review), $this->reviewData())->assertRedirect(route('login'));
    }

    public function test_review_owner_can_update_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            $this->reviewData(['rating' => 3, 'comment' => '更新後のコメントです。'])
        );

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新後のコメントです。',
        ]);
    }

    public function test_non_owner_cannot_update_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('reviews.update', $review), $this->reviewData())
            ->assertForbidden();
    }

    public function test_review_update_validates_rating_and_comment(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        foreach ([null, 0, 6, '2.5'] as $rating) {
            $this->actingAs($user)
                ->put(route('reviews.update', $review), $this->reviewData(['rating' => $rating]))
                ->assertSessionHasErrors('rating');
        }

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください']);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), $this->reviewData(['comment' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['comment' => 'コメントが長すぎます']);
    }
}
