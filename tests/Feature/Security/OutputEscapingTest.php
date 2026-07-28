<?php

namespace Tests\Feature\Security;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutputEscapingTest extends TestCase
{
    use RefreshDatabase;

    // 書籍、ジャンル、レビューの利用者入力がHTMLとして実行されずエスケープされることを確認する。
    public function test_book_pages_escape_user_supplied_content(): void
    {
        $script = '<script>alert("xss")</script>';
        $book = Book::factory()->create([
            'title' => $script,
            'author' => $script,
            'description' => $script,
        ]);
        $genre = Genre::factory()->create(['name' => $script]);
        $book->genres()->attach($genre);
        Review::factory()->create([
            'book_id' => $book->id,
            'comment' => $script,
        ]);

        foreach ([
            $this->get(route('books.index')),
            $this->get(route('books.show', $book)),
        ] as $response) {
            $response
                ->assertOk()
                ->assertSee(e($script), false)
                ->assertDontSee($script, false);
        }
    }

    // ジャンル画面に表示される利用者入力がHTMLエスケープされることを確認する。
    public function test_genre_pages_escape_user_supplied_content(): void
    {
        $script = '<img src=x onerror=alert("xss")>';
        $genre = Genre::factory()->create(['name' => $script]);
        $user = User::factory()->create();

        foreach ([
            $this->actingAs($user)->get(route('genres.index')),
            $this->actingAs($user)->get(route('genres.show', $genre)),
        ] as $response) {
            $response
                ->assertOk()
                ->assertSee(e($script), false)
                ->assertDontSee($script, false);
        }
    }

    // 通知に含まれる利用者入力がHTMLエスケープされることを確認する。
    public function test_notification_page_escapes_user_supplied_content(): void
    {
        $script = '<svg onload=alert("xss")>';
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => $script]);
        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3),
            'status' => 1,
        ]);
        $user->notify(new ReadingPlanNotification('three_days_before', $plan));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(e($script), false)
            ->assertDontSee($script, false);
    }
}
