<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexBookRequest $request): View
    {
        /** @var Builder<Book> $query */
        $query = Book::with('genres')->withAvg('reviews', 'rating')->withCount('reviews');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function (Builder $query) use ($request): void {
                $query->where('genres.id', $request->genre);
            });
        }

        /** @var array<string, list<array{string, 'asc'|'desc'}>> $sortOptions */
        $sortOptions = [
            'latest' => [
                ['created_at', 'desc'],
            ],
            'oldest' => [
                ['created_at', 'asc'],
            ],
            'rating' => [
                ['reviews_avg_rating', 'desc'],
                ['reviews_count', 'desc'],
            ],
            'title' => [
                ['title', 'asc'],
            ],
        ];

        if ($request->filled('sort')) {
            $sort = $request->input('sort', 'latest');
            foreach ($sortOptions[$sort] as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->latest();
        }

        $books = $query->paginate(10)->withQueryString();
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        $book = $user->books()->create($validated);
        $book->genres()->attach($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View
    {
        $book = Book::with([
            'genres',
            'reviews' => fn ($q) => $q->with('user')->withCount('likedByUsers'),
        ])->findOrFail($id);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, string $id): RedirectResponse
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $validated = $request->validated();
        $book->update($validated);
        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を更新しました');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $book = Book::findOrFail($id);
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました');
    }

    public function ranking(): View
    {
        $rankedBooks = Book::has('reviews')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
