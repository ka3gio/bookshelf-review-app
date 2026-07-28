<?php

namespace Tests\Feature\Reviews;

use App\Models\Review;
use App\Models\User;

class ReviewDeletionTest extends ReviewTestCase
{
    // ゲストによるレビュー削除を拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_deleting_a_review(): void
    {
        $review = Review::factory()->create();

        $this->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('login'));
    }

    // 所有者がレビューを削除すると関連いいねも削除されることを確認する。
    public function test_review_owner_can_delete_review_and_its_likes(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id]);
        $review->likedByUsers()->attach($liker);

        $response = $this->actingAs($owner)->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $review->book_id));
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
    }

    // 所有者以外がレビューを削除できないことを確認する。
    public function test_non_owner_cannot_delete_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();
    }
}
