<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\IndexBookRequest;
use App\Http\Requests\Api\v1\StoreBookRequest;
use App\Http\Requests\Api\v1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next): Response {
            $user = Auth::guard('sanctum')->user();

            if (! $user) {
                return response()->json([
                    'error' => '認証が必要です',
                    'error_code' => 'AUTHENTICATION_REQUIRED',
                ], 401);
            }

            $request->setUserResolver(fn () => $user);

            return $next($request);
        })->only(['store', 'update', 'destroy']);
    }

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
        if ($request->user()->cannot('create', Book::class)) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($request, $validated, $genreIds): Book {
            $book = $request->user()->books()->create($validated);
            $book->genres()->attach($genreIds);

            return $book;
        });

        $book->load(['user', 'genres']);

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $book): JsonResponse
    {
        $book = Book::find($book);

        if (! $book) {
            return $this->bookNotFoundResponse();
        }

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
    public function update(UpdateBookRequest $request, int $book): JsonResponse
    {
        $book = Book::find($book);

        if (! $book) {
            return $this->bookNotFoundResponse();
        }

        if ($request->user()->cannot('update', $book)) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds): void {
            $book->update($validated);
            $book->genres()->sync($genreIds);
        });

        $book->load(['user', 'genres'])->loadCount('reviews')->loadAvg('reviews', 'rating');

        return (new BookResource($book))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $book): JsonResponse
    {
        $book = Book::find($book);

        if (! $book) {
            return $this->bookNotFoundResponse();
        }

        if ($request->user()->cannot('delete', $book)) {
            return $this->forbiddenResponse();
        }

        $book->delete();

        return response()->json(null, 204);
    }

    private function bookNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'error' => '書籍が見つかりませんでした',
            'error_code' => 'RESOURCE_NOT_FOUND',
        ], 404);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'この操作を実行する権限がありません',
            'error_code' => 'FORBIDDEN',
        ], 403);
    }
}
