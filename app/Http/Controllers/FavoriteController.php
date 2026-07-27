<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = request()->user();
        $books = $user->favoriteBooks()->with('genres')->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Request $request, Book $book): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $bookId = $book->id;

        if ($user->favoriteBooks()->where('book_id', $bookId)->exists()) {
            // すでにお気に入りに登録されている場合は削除
            $user->favoriteBooks()->detach($bookId);

            return back()->with('success', 'お気に入りから削除しました');
        }

        // お気に入りに登録されていない場合は追加
        $user->favoriteBooks()->attach($bookId);

        return back()->with('success', 'お気に入りに追加しました');
    }
}
