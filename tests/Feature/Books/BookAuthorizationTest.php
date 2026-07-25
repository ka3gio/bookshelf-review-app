<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class BookAuthorizationTest extends BookTestCase
{
    public function test_guest_is_redirected_to_login_when_editing_updating_or_deleting_a_book(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.edit', $book))
            ->assertRedirect(route('login'));

        $this->put(route('books.update', $book))
            ->assertRedirect(route('login'));

        $this->delete(route('books.destroy', $book))
            ->assertRedirect(route('login'));
    }

    public function test_book_owner_can_view_the_edit_page(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('books.edit', $book))
            ->assertOk();
    }

    public function test_non_owner_cannot_edit_a_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('books.edit', $book))
            ->assertForbidden();
    }

    public function test_non_owner_cannot_update_a_book(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('books.update', $book), $this->bookData([$genre->id], ['isbn' => $book->isbn]))
            ->assertForbidden();
    }

    public function test_non_owner_cannot_delete_a_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('books.destroy', $book))
            ->assertForbidden();
    }
}
