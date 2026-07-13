<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;

use App\Models\Review;
use App\Models\Book;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReviewRequest $request, Book $book)
    {
        $book->reviews()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('books.show', $book->id)->with('success', 'レビューを投稿しました');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReviewRequest $request, string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        $validated = $request->validated();
        $review->update($validated);

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('delete', $review);
        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました');
    }
}
