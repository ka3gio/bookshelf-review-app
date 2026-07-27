<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
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
    public function show(string $id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReviewRequest $request, string $id): RedirectResponse
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
    public function destroy(string $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $this->authorize('delete', $review);
        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました');
    }

    public function like(Review $review, Request $request): RedirectResponse
    {
        if ($review->likedByUsers()->where('user_id', auth()->id())->exists()) {
            // すでにいいねしている場合は削除
            $review->likedByUsers()->detach(auth()->id());

            return back()->with('success', 'いいねを取り消しました');
        }

        // いいねしていない場合は追加
        $review->likedByUsers()->attach(auth()->id());

        return back()->with('success', 'いいねしました');
    }
}
