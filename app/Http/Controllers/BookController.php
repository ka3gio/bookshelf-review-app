<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;

use App\Models\Book;
use App\Models\Genre;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::with('genres')->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();
        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();
        $book = auth()->user()->books()->create($validated);
        $book->genres()->attach($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::findOrFail($id);
        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $genres = Genre::all();
        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, string $id)
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
    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました');
    }

    public function ranking()
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
