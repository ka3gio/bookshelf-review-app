<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class BookStoreTest extends BookTestCase
{
    public function test_guest_is_redirected_to_login_when_viewing_or_storing_a_book(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));

        $this->post(route('books.store'), $this->bookData([]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('books.create'))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_a_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $bookData = $this->bookData($genres->modelKeys());

        $response = $this->actingAs($user)->post(route('books.store'), $bookData);
        $book = Book::where('isbn', $bookData['isbn'])->firstOrFail();

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => $bookData['title'],
        ]);
        $this->assertEqualsCanonicalizing($genres->modelKeys(), $book->genres()->pluck('genres.id')->all());
    }

    public function test_book_store_validates_input(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        foreach ($this->invalidBookCases() as [$overrides, $field, $message, $duplicate]) {
            if ($duplicate ?? false) {
                Book::factory()->create(['isbn' => $overrides['isbn']]);
            }

            $this->actingAs($user)
                ->post(route('books.store'), $this->bookData([$genre->id], $overrides))
                ->assertSessionHasErrors([$field => $message]);
        }
    }
}
