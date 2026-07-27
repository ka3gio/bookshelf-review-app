<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = request()->user();
        $totalReviews = $user->reviews()->count();
        $booksRead = $user->readingPlans()
            ->where('status', ReadingPlanStatus::Completed->value)
            ->distinct()
            ->count('book_id');
        $averageRating = $user->reviews()->avg('rating');

        $ratingDistribution = $user->reviews()->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')->orderBy('rating', 'asc')
            ->pluck('count', 'rating');

        $ratingDistribution = collect([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0])
            ->replace($ratingDistribution);

        $topRatedBooks = $user->reviews()->with('book')
            ->where('rating', '>=', 4)
            ->orderBy('rating', 'desc')
            ->take(5)->get()->map(fn (Review $review): array => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ]);

        $genreRatings = Genre::query()
            ->join('book_genre', 'genres.id', '=', 'book_genre.genre_id')
            ->join('reviews', 'book_genre.book_id', '=', 'reviews.book_id')
            ->where('reviews.user_id', $user->id)
            ->selectRaw('
                genres.id as id,
                genres.name as name,
                COUNT(reviews.id) as count,
                AVG(reviews.rating) as average_rating
            ')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->limit(5)
            ->get();

        /** @var array<string, mixed> $stats */
        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
