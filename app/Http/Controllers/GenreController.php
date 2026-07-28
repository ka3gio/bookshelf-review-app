<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示する
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する
     *
     * @param  StoreGenreRequest  $request  バリデーション済みのリクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        Genre::create($validated);

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました');
    }

    /**
     * ジャンルの詳細を表示する
     *
     * @param  string  $id  ジャンルID
     * @return View ジャンル詳細画面
     */
    public function show(string $id): View
    {
        $genre = Genre::findOrFail($id);
        $books = $genre->books()->with('genres')->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する
     *
     * @param  string  $id  ジャンルID
     * @return View ジャンル編集画面
     */
    public function edit(string $id): View
    {
        $genre = Genre::findOrFail($id);

        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル情報を更新する
     *
     * @param  UpdateGenreRequest  $request  バリデーション済みのリクエスト
     * @param  string  $id  ジャンルID
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function update(UpdateGenreRequest $request, string $id): RedirectResponse
    {
        $validated = $request->validated();

        $genre = Genre::findOrFail($id);
        $genre->update($validated);

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました');
    }

    /**
     * ジャンルを削除する
     *
     * @param  string  $id  ジャンルID
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function destroy(string $id): RedirectResponse
    {
        $genre = Genre::findOrFail($id);

        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')
                ->with('error', '書籍に紐づいているジャンルは削除できません');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました');
    }
}
