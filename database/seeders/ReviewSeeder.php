<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class ReviewSeeder extends Seeder
{
    private const COMMENTS_BY_RATING = [
        1 => "あまり楽しむことができませんでした。\n自分には合わない内容だと感じました。",
        2 => "参考になる点はいくつかありました。\n全体としては少し物足りなく感じました。",
        3 => "全体として標準的な内容でした。\n気軽に読み進めることができました。",
        4 => "内容が充実していて分かりやすかったです。\n最後まで楽しく読むことができました。",
        5 => "とても満足できる内容でした。\nぜひ他の人にもおすすめしたい一冊です。",
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();
        $books = Book::query()->get();

        if ($users->count() < 4) {
            throw new LogicException('レビューの投入には、ユーザーが4件以上必要です。');
        }

        if ($books->isEmpty()) {
            throw new LogicException('レビューの投入には、書籍が1件以上必要です。');
        }

        $ratings = collect(range(1, 5))->shuffle()->values();
        $ratingIndex = 0;

        foreach ($books as $book) {
            $reviewers = $users
                ->shuffle()
                ->take(random_int(2, 4));

            foreach ($reviewers as $user) {
                if ($ratingIndex > 0 && $ratingIndex % $ratings->count() === 0) {
                    $ratings = $ratings->shuffle()->values();
                }

                $rating = $ratings[$ratingIndex % $ratings->count()];

                Review::create([
                    'book_id' => $book->getKey(),
                    'user_id' => $user->getKey(),
                    'rating' => $rating,
                    'comment' => self::COMMENTS_BY_RATING[$rating],
                ]);

                $ratingIndex++;
            }
        }
    }
}
