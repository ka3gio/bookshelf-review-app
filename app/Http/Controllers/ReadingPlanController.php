<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画一覧を表示する
     *
     * @param  IndexReadingPlanRequest  $request  バリデーション済みの絞り込み条件
     * @return View 読書計画一覧画面
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $currentStatus = $request->integer('status');

        $readingPlans = $user
            ->readingPlans()
            ->with('book')
            ->when(
                $currentStatus,
                fn ($query) => $query->where('status', $currentStatus)
            )
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画の登録画面を表示する
     *
     * @return View 読書計画登録画面
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する
     *
     * @param  StoreReadingPlanRequest  $request  バリデーション済みのリクエスト
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました');
    }

    /**
     * 読書計画の編集画面を表示する
     *
     * @param  string  $id  読書計画ID
     * @return View 読書計画編集画面
     */
    public function edit(string $id): View
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する
     *
     * @param  UpdateReadingPlanRequest  $request  バリデーション済みのリクエスト
     * @param  string  $id  読書計画ID
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function update(UpdateReadingPlanRequest $request, string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('update', $readingPlan);
        $validated = $request->validated();

        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $validated['status'] = ReadingPlanStatus::InProgress;
            $validated['completed_at'] = null;
        }

        $readingPlan->update($validated);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました');
    }

    /**
     * 読書計画を削除する
     *
     * @param  string  $id  読書計画ID
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function destroy(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('delete', $readingPlan);
        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました');
    }

    /**
     * 読書計画を進行中に変更する
     *
     * @param  string  $id  読書計画ID
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function inprogress(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('inprogress', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を進行中にしました");
    }

    /**
     * 読書計画を読了済みに変更する
     *
     * @param  string  $id  読書計画ID
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function complete(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', "『{$readingPlan->book->title}』を読了しました");
    }
}
