<?php

namespace Tests\Feature\Books;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class BookTestCase extends TestCase
{
    use RefreshDatabase;

    protected function bookData(array $genreIds, array $overrides = []): array
    {
        return array_replace([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2024-01-01',
            'description' => '書籍の説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => $genreIds,
        ], $overrides);
    }

    protected function invalidBookCases(): array
    {
        return [
            [['title' => ''], 'title', 'タイトルを入力してください', false],
            [['title' => str_repeat('a', 256)], 'title', 'タイトルが長すぎます', false],
            [['author' => ''], 'author', '著者名を入力してください', false],
            [['author' => str_repeat('a', 101)], 'author', '著者名が長すぎます', false],
            [['isbn' => ''], 'isbn', 'ISBNを入力してください', false],
            [['isbn' => '123456789012'], 'isbn', 'ISBNの文字数が不正です', false],
            [['isbn' => '9784000000000'], 'isbn', 'その書籍は既に登録されています', true],
            [['published_date' => ''], 'published_date', '出版日を入力してください', false],
            [['published_date' => 'invalid-date'], 'published_date', '出版日を日付の形式で入力してください', false],
            [['description' => str_repeat('a', 1001)], 'description', '説明が長すぎます', false],
            [['image_url' => 'https://example.com/'.str_repeat('a', 240)], 'image_url', '画像リンクが長すぎます', false],
            [['image_url' => 'invalid-url'], 'image_url', '画像リンクをURLで入力してください', false],
        ];
    }
}
