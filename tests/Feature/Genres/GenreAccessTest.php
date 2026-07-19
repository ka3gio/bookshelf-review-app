<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class GenreAccessTest extends GenreTestCase
{
    public function test_authenticated_user_can_view_genre_screens(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)->get(route('genres.index'))->assertOk();
        $this->actingAs($user)->get(route('genres.show', $genre))->assertOk();
        $this->actingAs($user)->get(route('genres.create'))->assertOk();
        $this->actingAs($user)->get(route('genres.edit', $genre))->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_genre_screens(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.index'))->assertRedirect(route('login'));
        $this->get(route('genres.show', $genre))->assertRedirect(route('login'));
        $this->get(route('genres.create'))->assertRedirect(route('login'));
        $this->get(route('genres.edit', $genre))->assertRedirect(route('login'));
    }

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
}
