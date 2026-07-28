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
     * レビュー一覧用のアクション
     *
     * @return void 戻り値なし
     */
    public function index(): void
    {
        //
    }

    /**
     * レビュー登録画面用のアクション
     *
     * @return void 戻り値なし
     */
    public function create(): void
    {
        //
    }

    /**
     * 書籍にレビューを登録する
     *
     * @param  StoreReviewRequest  $request  バリデーション済みのリクエスト
     * @param  Book  $book  レビュー対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
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
     * レビュー詳細用のアクション
     *
     * @param  string  $id  レビューID
     * @return void 戻り値なし
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * レビュー編集画面を表示する
     *
     * @param  string  $id  レビューID
     * @return View レビュー編集画面
     */
    public function edit(string $id): View
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する
     *
     * @param  UpdateReviewRequest  $request  バリデーション済みのリクエスト
     * @param  string  $id  レビューID
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
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
     * レビューを削除する
     *
     * @param  string  $id  レビューID
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function destroy(string $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $this->authorize('delete', $review);
        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました');
    }

    /**
     * レビューのいいね状態を切り替える
     *
     * @param  Review  $review  対象のレビュー
     * @param  Request  $request  リクエスト
     * @return RedirectResponse 直前の画面へのリダイレクト
     */
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
