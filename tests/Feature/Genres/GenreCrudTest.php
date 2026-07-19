<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class GenreCrudTest extends GenreTestCase
{
    public function test_authenticated_user_can_create_a_genre(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('genres.store'), ['name' => '小説']);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', ['name' => '小説']);
    }

    public function test_authenticated_user_can_update_a_genre(): void
    {
        $genre = Genre::factory()->create(['name' => '旧ジャンル名']);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('genres.update', $genre), ['name' => '新ジャンル名']);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '新ジャンル名']);
    }

    public function test_genre_without_books_can_be_deleted(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->create());

        $response = $this->actingAs(User::factory()->create())
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
