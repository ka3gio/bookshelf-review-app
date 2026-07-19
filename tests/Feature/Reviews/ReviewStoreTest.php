<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\User;

class ReviewStoreTest extends ReviewTestCase
{
    public function test_authenticated_user_can_post_a_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $reviewData = $this->reviewData();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), $reviewData);

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $reviewData['rating'],
            'comment' => $reviewData['comment'],
        ]);
    }

    public function test_guest_is_redirected_to_login_when_posting_a_review(): void
    {
        $book = Book::factory()->create();

        $this->post(route('reviews.store', $book), $this->reviewData())
            ->assertRedirect(route('login'));
    }

    public function test_review_store_validates_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        foreach ([null, 0, 6, '2.5'] as $rating) {
            $this->actingAs($user)
                ->post(route('reviews.store', $book), $this->reviewData(['rating' => $rating]))
                ->assertSessionHasErrors('rating');
        }
    }

    public function test_review_store_validates_comment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください']);

        $this->actingAs($user)
            ->post(route('reviews.store', $book), $this->reviewData(['comment' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['comment' => 'コメントが長すぎます']);
    }
}
