<?php

namespace App\Http\Controllers;

use App\Models\Genre;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $total_reviews = $user->reviews()->count();
        $books_read = $user->readingPlans()->where('plan_status', 3)->distinct()->count('book_id');
        $average_rating = $user->reviews()->avg('rating');

        $rating_distribution = $user->reviews()->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')->orderBy('rating', 'asc')
            ->pluck('count', 'rating');

        $rating_distribution = collect([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0])
            ->replace($rating_distribution);

        $top_rated_books = $user->reviews()->with('book')
            ->where('rating', '>=', 4)
            ->orderBy('rating', 'desc')
            ->take(5)->get()->map(fn($review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ]);

        $genre_ratings = Genre::query()
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

        $stats = [
            'summary' => [
                'total_reviews' => $total_reviews,
                'books_read' => $books_read,
                'average_rating' => $average_rating,
            ],
            'rating_distribution' => $rating_distribution,
            'top_rated_books' => $top_rated_books,
            'genre_ratings' => $genre_ratings,
        ];
        return view('reports.index', compact('stats'));
    }
}
