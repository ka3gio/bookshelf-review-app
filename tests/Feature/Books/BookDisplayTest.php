<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;

class BookDisplayTest extends BookTestCase
{
    public function test_root_path_displays_the_book_index(): void
    {
        Book::factory()->create(['title' => 'トップ画面の書籍']);

        $this->get('/')
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertSee('トップ画面の書籍');
    }

    // 書籍一覧に書籍の概要が表示されることを確認する。
    public function test_book_index_displays_book_summary(): void
    {
        $book = Book::factory()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'image_url' => 'https://example.com/book.jpg',
        ]);
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book->genres()->attach($genre);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee(['テスト書籍', 'テスト著者', '技術書', 'https://example.com/book.jpg']);
    }

    // 書籍詳細に書籍情報とレビューが表示されることを確認する。
    public function test_book_show_displays_book_details_and_reviews(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create(['name' => 'レビューユーザー']);
        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => '書籍の説明です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book->genres()->attach($genre);
        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'とても参考になりました。',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee([
                'テスト書籍', 'テスト著者', '9784000000000', '2024-01-01',
                '書籍の説明です。', 'https://example.com/book.jpg', '技術書',
                'レビューユーザー', 'とても参考になりました。',
            ]);
    }
}
