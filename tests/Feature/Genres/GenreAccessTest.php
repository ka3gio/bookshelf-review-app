<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

class GenreAccessTest extends GenreTestCase
{
    // 認証済みユーザーがジャンル関連画面を表示できることを確認する。
    #[DataProvider('genreScreenCases')]
    public function test_authenticated_user_can_view_genre_screens(
        string $routeName,
        bool $requiresGenre
    ): void {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName, $requiresGenre ? $genre : []))
            ->assertOk();
    }

    // ゲストのジャンル関連画面アクセスを拒否することを確認する。
    #[DataProvider('genreScreenCases')]
    public function test_guest_is_redirected_to_login_from_genre_screens(
        string $routeName,
        bool $requiresGenre
    ): void {
        $genre = Genre::factory()->create();

        $this->get(route($routeName, $requiresGenre ? $genre : []))
            ->assertRedirect(route('login'));
    }

    // ゲストによるジャンルの登録・更新・削除を拒否することを確認する。
    #[DataProvider('genreMutationCases')]
    public function test_guest_cannot_mutate_genres(
        string $method,
        string $routeName
    ): void {
        $genre = Genre::factory()->create(['name' => '変更前ジャンル']);
        $url = route($routeName, $routeName === 'genres.store' ? [] : $genre);

        $response = match ($method) {
            'post' => $this->post($url, ['name' => '新規ジャンル']),
            'put' => $this->put($url, ['name' => '変更後ジャンル']),
            'delete' => $this->delete($url),
        };

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '変更前ジャンル',
        ]);
        $this->assertDatabaseMissing('genres', ['name' => '新規ジャンル']);
        $this->assertDatabaseMissing('genres', ['name' => '変更後ジャンル']);
    }

    // ジャンル一覧に各ジャンルの書籍数を表示することを確認する。
    public function test_genre_index_displays_genres_and_book_counts(): void
    {
        $genreWithBooks = Genre::factory()->create(['name' => '小説']);
        $genreWithoutBooks = Genre::factory()->create(['name' => '技術書']);
        $genreWithBooks->books()->attach(Book::factory()->count(2)->create());

        $this->actingAs(User::factory()->create())
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee(['小説', '2冊', '技術書', '0冊']);
    }

    // ジャンル詳細に所属書籍だけを表示することを確認する。
    public function test_genre_show_displays_only_books_belonging_to_genre(): void
    {
        $genre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $genreBook = Book::factory()->create(['title' => '対象ジャンルの書籍']);
        $otherGenreBook = Book::factory()->create(['title' => '対象外ジャンルの書籍']);
        $genre->books()->attach($genreBook);
        $otherGenre->books()->attach($otherGenreBook);

        $this->actingAs(User::factory()->create())
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('対象ジャンルの書籍')
            ->assertDontSee('対象外ジャンルの書籍');
    }

    public static function genreScreenCases(): array
    {
        return [
            'index' => ['genres.index', false],
            'show' => ['genres.show', true],
            'create' => ['genres.create', false],
            'edit' => ['genres.edit', true],
        ];
    }

    public static function genreMutationCases(): array
    {
        return [
            'store' => ['post', 'genres.store'],
            'update' => ['put', 'genres.update'],
            'delete' => ['delete', 'genres.destroy'],
        ];
    }
}
