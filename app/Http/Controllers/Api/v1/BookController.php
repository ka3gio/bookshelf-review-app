<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\IndexBookRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        /** @var Builder<Book> $query */
        $query = Book::with(['user', 'genres'])
            ->withCount('reviews')->withAvg('reviews', 'rating');

        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? null;
        if (filled($keyword)) {
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('author', 'like', '%'.$keyword.'%');
            });
        }

        $genreId = $validated['genre_id'] ?? null;
        if (filled($genreId)) {
            $query->whereHas('genres', fn (Builder $query): Builder => $query->whereKey($genreId));
        }

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 20;

        $books = $query->paginate($perPage, ['*'], 'page', $page);

        return BookResource::collection($books)->response()->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $this->authorize('create', Book::class);

        $validated = $request->validated();
        $genreIds = $validated['genres'] ?? [];
        unset($validated['genres']);

        $book = $request->user()->books()->create($validated);
        if ($genreIds) {
            $book->genres()->attach($genreIds);
        }

        $book->load(['user', 'genres']);

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load([
            'user',
            'genres',
            'reviews' => fn ($query) => $query
                ->with('user')
                ->withCount('likedByUsers'),
        ])->loadCount('reviews')->loadAvg('reviews', 'rating');

        return (new BookResource($book))->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();
        $genreIds = $validated['genres'] ?? [];
        unset($validated['genres']);

        $book->update($validated);
        $book->genres()->sync($genreIds);

        $book->load(['user', 'genres'])->loadCount('reviews')->loadAvg('reviews', 'rating');

        return (new BookResource($book))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
