<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id');
        $reviewCounts = array_combine(
            Book::pluck('id')->all(),
            collect([2, 2, 2, 2, 2, 3, 3, 4, 4, 4, 4])->shuffle()->all()
        );

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
