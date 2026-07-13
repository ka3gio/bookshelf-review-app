<?php

namespace Tests\Feature\Genres;

use App\Models\Genre;
use App\Models\User;

class GenreValidationTest extends GenreTestCase
{
    public function test_genre_store_validates_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('genres.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名を入力してください']);

        $this->actingAs($user)
            ->post(route('genres.store'), ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors(['name' => 'ジャンル名が長すぎます']);

        Genre::factory()->create(['name' => '小説']);
        $this->actingAs($user)
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています']);
    }

    public function test_genre_update_validates_name_and_allows_its_current_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名を入力してください']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors(['name' => 'ジャンル名が長すぎます']);

        $otherGenre = Genre::factory()->create(['name' => '技術書']);
        $this->actingAs($user)
            ->put(route('genres.update', $genre), ['name' => $otherGenre->name])
            ->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています']);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), ['name' => $genre->name])
            ->assertRedirect(route('genres.index'));
    }
}
