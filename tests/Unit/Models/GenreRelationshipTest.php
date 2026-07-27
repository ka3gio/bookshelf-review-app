<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // ジャンルに複数書籍を紐付けられることを確認する。
    public function test_genre_can_belong_to_multiple_books(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create([]);

        $genre->books()->attach($books->modelKeys());

        $this->assertCount(2, $genre->books);
        $this->assertTrue($genre->books->contains($books->first()));
        $this->assertTrue($genre->books->contains($books->last()));
    }
}
