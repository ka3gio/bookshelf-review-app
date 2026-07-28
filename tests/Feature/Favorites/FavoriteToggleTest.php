<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_toggle_uses_the_plural_url(): void
    {
        $book = Book::factory()->create();

        $this->assertSame(
            "/books/{$book->id}/favorites",
            route('favorites.toggle', $book, absolute: false)
        );
    }

    // 認証済みユーザーが書籍をお気に入りへ追加できることを確認する。
    public function test_authenticated_user_can_add_a_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // お気に入り済み書籍を再操作すると解除されることを確認する。
    public function test_favoriting_a_book_again_removes_it(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // 同じお気に入りの重複レコードが作成されないことを確認する。
    public function test_user_cannot_create_duplicate_favorite_records(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // ゲストのお気に入り操作を拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_toggling_a_favorite(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));
    }
}
