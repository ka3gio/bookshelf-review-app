<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_index_uses_the_plural_url(): void
    {
        $this->assertSame('/favorites', route('favorites.index', absolute: false));
    }

    // ログイン中ユーザーのお気に入り書籍だけを表示することを確認する。
    public function test_favorite_index_displays_only_current_users_favorite_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $favoriteBook = Book::factory()->create(['title' => '自分のお気に入り書籍']);
        $otherFavoriteBook = Book::factory()->create(['title' => '他人のお気に入り書籍']);
        $user->favoriteBooks()->attach($favoriteBook);
        $otherUser->favoriteBooks()->attach($otherFavoriteBook);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('自分のお気に入り書籍')
            ->assertDontSee('他人のお気に入り書籍');
    }

    // ゲストのお気に入り一覧アクセスを拒否することを確認する。
    public function test_guest_is_redirected_to_login_when_viewing_favorites(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }
}
