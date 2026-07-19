<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;

class BookAuthorizationTest extends BookTestCase
{
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
