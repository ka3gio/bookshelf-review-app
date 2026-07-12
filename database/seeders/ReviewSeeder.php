<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    private const REVIEW_COUNT_PATTERNS = [
        [2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
        [2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 4],
        [2, 2, 2, 3, 3, 3, 3, 3, 3, 4, 4],
        [2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4],
        [2, 2, 2, 2, 2, 3, 3, 4, 4, 4, 4],
        [2, 2, 2, 2, 2, 2, 4, 4, 4, 4, 4],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id');
        $reviewCounts = collect(self::REVIEW_COUNT_PATTERNS)->random();
        shuffle($reviewCounts);
        $reviewCounts = array_combine(Book::pluck('id')->all(), $reviewCounts);

        foreach ($reviewCounts as $bookId => $reviewCount) {
            $reviewUsers = $userIds->shuffle()->take($reviewCount);
            foreach ($reviewUsers as $userId) {
                Review::factory()->create([
                    'book_id' => $bookId,
                    'user_id' => $userId,
                    'rating' => rand(3, 5),
                ]);
            }
        }
    }
}
