<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;

class ReviewDisplayTest extends ReviewTestCase
{
    // 書籍詳細に対象書籍のレビューだけを表示することを確認する。
    public function test_book_show_displays_only_reviews_for_that_book(): void
    {
        $book = Book::factory()->create();
        $otherBook = Book::factory()->create();
        Review::factory()->create([
            'book_id' => $book->id,
            'comment' => '対象書籍のレビューです。',
        ]);
        Review::factory()->create([
            'book_id' => $otherBook->id,
            'comment' => '別の書籍のレビューです。',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('対象書籍のレビューです。')
            ->assertDontSee('別の書籍のレビューです。');
    }
}
