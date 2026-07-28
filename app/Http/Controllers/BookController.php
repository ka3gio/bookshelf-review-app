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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索・絞り込み・並び替えて表示する
     *
     * @param  IndexBookRequest  $request  バリデーション済みの検索条件
     * @return View 書籍一覧画面
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
     * 書籍登録画面を表示する
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する
     *
     * @param  StoreBookRequest  $request  バリデーション済みのリクエスト
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($user, $validated, $genreIds): Book {
            $book = $user->books()->create($validated);
            $book->genres()->attach($genreIds);

            return $book;
        });

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました');
    }

    /**
     * 書籍の詳細を表示する
     *
     * @param  string  $id  書籍ID
     * @return View 書籍詳細画面
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
     * 書籍編集画面を表示する
     *
     * @param  string  $id  書籍ID
     * @return View 書籍編集画面
     */
    public function edit(string $id): View
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新する
     *
     * @param  UpdateBookRequest  $request  バリデーション済みのリクエスト
     * @param  string  $id  書籍ID
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(UpdateBookRequest $request, string $id): RedirectResponse
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds): void {
            $book->update($validated);
            $book->genres()->sync($genreIds);
        });

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を更新しました');
    }

    /**
     * 書籍を削除する
     *
     * @param  string  $id  書籍ID
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function destroy(string $id): RedirectResponse
    {
        $book = Book::findOrFail($id);
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました');
    }

    /**
     * レビュー評価に基づく書籍ランキングを表示する
     *
     * @return View 書籍ランキング画面
     */
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
